import pandas as pd
import joblib
import os
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import OneHotEncoder
from sklearn.compose import ColumnTransformer
from sklearn.pipeline import Pipeline

script_dir = os.path.dirname(os.path.abspath(__file__))
models_dir = os.path.abspath(os.path.join(script_dir, '../models'))
csv_path = os.path.join(script_dir, 'historical_billing_records.csv')
model_save_path = os.path.join(models_dir, 'cost_regressor.pkl')

os.makedirs(models_dir, exist_ok=True)

print(f"Loading clinical billing data from {csv_path}...")
try:
    df = pd.read_csv(csv_path)
except FileNotFoundError:
    print("ERROR: historical_billing_records.csv not found. Please upload it via the admin panel first.")
    exit()

required_cols = ['disease_name', 'severity_level', 'pet_weight_kg', 'treatment_duration_days', 'clinic_tier', 'actual_total_cost']
if not all(col in df.columns for col in required_cols):
    print("ERROR: The CSV is missing required columns. Training aborted.")
    exit()

X = df[['disease_name', 'severity_level', 'pet_weight_kg', 'treatment_duration_days', 'clinic_tier']]
y = df['actual_total_cost']

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

categorical_features = ['species', 'disease_name', 'severity_level', 'clinic_tier']
numerical_features = ['pet_weight_kg', 'treatment_duration_days']

preprocessor = ColumnTransformer(transformers=[
    ('cat', OneHotEncoder(handle_unknown='ignore'), categorical_features),
    ('num', 'passthrough', numerical_features)
])

regressor = Pipeline(steps=[
    ('preprocessor', preprocessor),
    ('model', RandomForestRegressor(n_estimators=100, random_state=42))
])

print("Training the Random Forest regression model on the latest data...")
regressor.fit(X_train, y_train)

accuracy_score = regressor.score(X_test, y_test)
print(f"Model Accuracy (R^2 Score): {accuracy_score:.2f}")

joblib.dump(regressor, model_save_path)
print(f"SUCCESS: Updated cost regression pipeline saved to '{model_save_path}'.")