import os
import cv2
import sys
import datetime
import numpy as np
import pandas as pd
import tensorflow as tf
import joblib
import json
import subprocess
import shutil
from flask import Flask, request, jsonify
from flask_cors import CORS
from PIL import Image
from tensorflow.keras.models import load_model

app = Flask(__name__, static_folder='../static')
CORS(app)
os.makedirs('./static/uploads', exist_ok=True)

image_model = None
cost_model = None
class_indices = None
labels_map = None
pricing_df = None

def load_all_models():
    """Loads or reloads all models and CSV data into memory."""
    global image_model, cost_model, class_indices, labels_map, pricing_df
    try:
        image_model = load_model('./models/multiclass_skin_classifier.h5')
        cost_model = joblib.load('./models/cost_regressor.pkl')
        
        with open('models/class_indices.json', 'r') as f:
            class_indices = json.load(f)
        labels_map = {v: k for k, v in class_indices.items()}
        
        if os.path.exists('ml/clinic_prices.csv'):
            pricing_df = pd.read_csv('ml/clinic_prices.csv')
            
        print("Models and dataset loaded successfully.")
    except Exception as e:
        print(f"Error loading models or CSV: {e}")

load_all_models()

def make_gradcam_heatmap(img_array, model, class_index, last_conv_layer_name="Conv_1"):
    grad_model = tf.keras.models.Model(
        [model.inputs], [model.get_layer(last_conv_layer_name).output, model.output]
    )
    with tf.GradientTape() as tape:
        last_conv_layer_output, preds = grad_model(img_array)
        class_channel = preds[:, class_index]
    grads = tape.gradient(class_channel, last_conv_layer_output)
    pooled_grads = tf.reduce_mean(grads, axis=(0, 1, 2))
    
    last_conv_layer_output = last_conv_layer_output[0]
    heatmap = last_conv_layer_output @ pooled_grads[..., tf.newaxis]
    heatmap = tf.squeeze(heatmap)
    heatmap = tf.maximum(heatmap, 0) / tf.math.reduce_max(heatmap)
    return heatmap.numpy()

@app.route('/predict-image', methods=['POST'])
def predict_image():
    if 'image' not in request.files:
        return jsonify({"error": "No image uploaded"}), 400
        
    file = request.files['image']
    img_path = os.path.join('./static/uploads', file.filename)
    file.save(img_path)
    
    img = Image.open(img_path).convert('RGB').resize((224, 224))
    img_array = np.expand_dims(np.array(img) / 255.0, axis=0)
    
    predictions = image_model.predict(img_array)[0]
    top_indices = predictions.argsort()[-3:][::-1]
    
    possible_diseases = [
        {"disease": labels_map[i], "probability": round(float(predictions[i]) * 100, 2)}
        for i in top_indices
    ]
    
    primary_index = top_indices[0]
    primary_label = labels_map[primary_index]
    
    heatmap = make_gradcam_heatmap(img_array, image_model, primary_index, "Conv_1")
    original_img = cv2.imread(img_path)
    original_img = cv2.resize(original_img, (224, 224))
    heatmap = cv2.resize(heatmap, (original_img.shape[1], original_img.shape[0]))
    
    heatmap = np.uint8(255 * heatmap)
    jet_map = cv2.applyColorMap(heatmap, cv2.COLORMAP_JET)
    superimposed_img = cv2.addWeighted(jet_map, 0.4, original_img, 0.6, 0)
    
    heatmap_filename = f"heatmap_{file.filename}"
    heatmap_path = os.path.join('./static/uploads', heatmap_filename)
    cv2.imwrite(heatmap_path, superimposed_img)
    
    return jsonify({
        "status": "success",
        "primary_classification": primary_label,
        "possible_diseases": possible_diseases,
        "heatmap_url": f"/static/uploads/{heatmap_filename}",
        "analysis_time": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    })

@app.route('/predict-cost', methods=['POST'])
def predict_cost():
    if not cost_model:
        return jsonify({"error": "Cost regression model is not loaded. Please train or upload data first."}), 500

    try:
        data = request.get_json()
        
        species = data.get('species', 'dog')
        disease_name = data.get('disease_name', 'Dermatitis')
        severity_level = data.get('severity_level', 'Mild')
        pet_weight_kg = float(data.get('pet_weight_kg', 5.0))
        treatment_duration_days = int(data.get('treatment_duration_days', 7))
        clinic_tier = data.get('clinic_tier', 'Standard')
        
        input_df = pd.DataFrame([{
            'species': species,
            'disease_name': disease_name,
            'severity_level': severity_level,
            'pet_weight_kg': pet_weight_kg,
            'treatment_duration_days': treatment_duration_days,
            'clinic_tier': clinic_tier
        }])
        
        estimated_cost = cost_model.predict(input_df)[0]
        
        return jsonify({
            "status": "success",
            "species": species,
            "disease_name": disease_name,
            "estimated_cost": round(float(estimated_cost), 2),
            "currency": "₱"
        })
        
    except Exception as e:
        return jsonify({"error": str(e)}), 400

@app.route('/correct-classification', methods=['POST'])
def correct_classification():
    data = request.json
    image_path = data.get('image_path')
    correct_label = data.get('correct_label')
    
    if not image_path or not correct_label:
        return jsonify({"error": "Missing parameters"}), 400
        
    if image_path.startswith('/'):
        image_path = image_path[1:]
        
    dest_dir = os.path.join('dataset', correct_label)
        
    os.makedirs(dest_dir, exist_ok=True)

    filename = os.path.basename(image_path)
    dest_path = os.path.join(dest_dir, filename)
    
    try:
        shutil.copy(image_path, dest_path)
        return jsonify({"status": "success", "message": f"Image added to {correct_label} dataset."})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/training-log', methods=['GET'])
def training_log():
    try:
        if os.path.exists('ml/training_log.txt'):
            with open('ml/training_log.txt', 'r') as f:
                return jsonify({"log": f.read()})
        return jsonify({"log": "Initializing terminal..."})
    except Exception as e:
        return jsonify({"log": "Reading log..."})

@app.route('/retrain-model', methods=['POST'])
def retrain_model():
    try:
        with open('ml/training_log.txt', 'w') as log_file:
            log_file.write("Starting AI Retraining Process...\n")
            log_file.flush()
            
            log_file.write("\n--- Step 1: Rebuilding Dataset CSV ---\n")
            log_file.flush()
            subprocess.run([sys.executable, '-u', 'ml/create_multiclass_csv.py'], stdout=log_file, stderr=subprocess.STDOUT, check=True)
            
            log_file.write("\n--- Step 2: Training Model (This may take a few minutes) ---\n")
            log_file.flush()
            subprocess.run([sys.executable, '-u', 'ml/train_multiclass_model.py'], stdout=log_file, stderr=subprocess.STDOUT, check=True)
            
            log_file.write("\n--- Step 3: Reloading newly trained model into memory ---\n")
            log_file.flush()
            load_all_models()
            
            log_file.write("\nSUCCESS: AI Model is fully retrained and online!\n")
            
        return jsonify({"status": "success", "message": "Model retrained and loaded successfully!"})
    except subprocess.CalledProcessError as e:
        with open('ml/training_log.txt', 'a') as log_file:
            log_file.write(f"\n[ERROR] Script failed with exit code {e.returncode}\n")
        return jsonify({"error": f"Training script failed"}), 500
    except Exception as e:
        with open('ml/training_log.txt', 'a') as log_file:
            log_file.write(f"\n[ERROR] {str(e)}\n")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(port=5000, debug=True)