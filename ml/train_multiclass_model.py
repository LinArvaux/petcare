import pandas as pd
import tensorflow as tf
from tensorflow.keras.applications import MobileNetV2
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D
from tensorflow.keras.models import Model
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from sklearn.model_selection import train_test_split
import json
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
models_dir = os.path.abspath(os.path.join(script_dir, '../models'))

os.makedirs(models_dir, exist_ok=True)

csv_path = os.path.join(script_dir, 'multiclass_annotations.csv')
df = pd.read_csv(csv_path)

num_classes = df['label'].nunique()
print(f"Training on {num_classes} classes...")

train_df, val_df = train_test_split(df, test_size=0.2, random_state=123, stratify=df['label'])

train_datagen = ImageDataGenerator(
    rescale=1./255,
    rotation_range=20,       
    width_shift_range=0.2,   
    height_shift_range=0.2,  
    shear_range=0.2,         
    zoom_range=0.2,          
    horizontal_flip=True     
)

val_datagen = ImageDataGenerator(rescale=1./255)

train_generator = train_datagen.flow_from_dataframe(
    dataframe=train_df, directory=None, x_col='filename', y_col='label',
    batch_size=32, seed=123, class_mode='categorical', target_size=(224, 224)
)

val_generator = val_datagen.flow_from_dataframe(
    dataframe=val_df, directory=None, x_col='filename', y_col='label',
    batch_size=32, seed=123, class_mode='categorical', target_size=(224, 224)
)
indices_path = os.path.join(models_dir, 'class_indices.json')
with open(indices_path, 'w') as f:
    json.dump(train_generator.class_indices, f)

base_model = MobileNetV2(weights='imagenet', include_top=False, input_shape=(224, 224, 3))
base_model.trainable = False 

x = base_model.output
x = GlobalAveragePooling2D()(x)
x = Dense(128, activation='relu')(x)
predictions = Dense(num_classes, activation='softmax')(x)

model = Model(inputs=base_model.input, outputs=predictions)
model.compile(optimizer='adam', loss='categorical_crossentropy', metrics=['accuracy'])

print("\n--- Phase 1: Training Top Layers (20 Epochs) ---")
model.fit(train_generator, validation_data=val_generator, epochs=20, verbose=2)

print("\n--- Phase 2: Fine-Tuning Deep Layers (10 Epochs) ---")
base_model.trainable = True

for layer in base_model.layers[:-20]:
    layer.trainable = False

model.compile(optimizer=tf.keras.optimizers.Adam(1e-5), loss='categorical_crossentropy', metrics=['accuracy'])

model.fit(train_generator, validation_data=val_generator, epochs=10, verbose=2)

model_save_path = os.path.join(models_dir, 'multiclass_skin_classifier.h5')
model.save(model_save_path)
print(f"\nSUCCESS: Multi-class Image model and indices saved to '{models_dir}'.")