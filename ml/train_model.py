"""
Train Delivery Time Prediction Model
Trains a regression model to predict delivery time based on order features
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib
import json
import os

def load_data(csv_file='synthetic_delivery_data.csv'):
    """Load the synthetic dataset"""
    if not os.path.exists(csv_file):
        raise FileNotFoundError(f"Dataset file '{csv_file}' not found. Please run generate_synthetic_data.py first.")
    
    df = pd.read_csv(csv_file)
    return df

def prepare_features(df):
    """Prepare features for training"""
    # Create a copy to avoid modifying original
    df_processed = df.copy()
    
    # Encode categorical variables
    label_encoders = {}
    categorical_cols = ['municipality', 'barangay', 'postal_code']
    
    for col in categorical_cols:
        le = LabelEncoder()
        df_processed[col + '_encoded'] = le.fit_transform(df_processed[col])
        label_encoders[col] = le
    
    # Select features for training
    feature_cols = [
        'distance_km',
        'latitude',
        'longitude',
        'municipality_encoded',
        'barangay_encoded',
        'postal_code_encoded',
        'time_of_order',
        'day_of_week',
        'order_size'
    ]
    
    X = df_processed[feature_cols]
    y = df_processed['delivery_time_minutes']
    
    return X, y, label_encoders, feature_cols

def train_models(X, y):
    """Train both Linear Regression and Random Forest models"""
    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )
    
    # Train Linear Regression
    print("Training Linear Regression model...")
    lr_model = LinearRegression()
    lr_model.fit(X_train, y_train)
    
    # Evaluate Linear Regression
    lr_pred = lr_model.predict(X_test)
    lr_mae = mean_absolute_error(y_test, lr_pred)
    lr_rmse = np.sqrt(mean_squared_error(y_test, lr_pred))
    lr_r2 = r2_score(y_test, lr_pred)
    
    print(f"Linear Regression - MAE: {lr_mae:.2f}, RMSE: {lr_rmse:.2f}, R²: {lr_r2:.4f}")
    
    # Train Random Forest
    print("\nTraining Random Forest model...")
    rf_model = RandomForestRegressor(
        n_estimators=100,
        max_depth=10,
        min_samples_split=5,
        random_state=42,
        n_jobs=-1
    )
    rf_model.fit(X_train, y_train)
    
    # Evaluate Random Forest
    rf_pred = rf_model.predict(X_test)
    rf_mae = mean_absolute_error(y_test, rf_pred)
    rf_rmse = np.sqrt(mean_squared_error(y_test, rf_pred))
    rf_r2 = r2_score(y_test, rf_pred)
    
    print(f"Random Forest - MAE: {rf_mae:.2f}, RMSE: {rf_rmse:.2f}, R²: {rf_r2:.4f}")
    
    # Choose best model (based on R² score)
    if rf_r2 > lr_r2:
        print("\nRandom Forest performs better. Using Random Forest model.")
        return rf_model, 'random_forest', {
            'mae': rf_mae,
            'rmse': rf_rmse,
            'r2': rf_r2
        }
    else:
        print("\nLinear Regression performs better. Using Linear Regression model.")
        return lr_model, 'linear_regression', {
            'mae': lr_mae,
            'rmse': lr_rmse,
            'r2': lr_r2
        }

def save_model(model, model_type, label_encoders, feature_cols, metrics, output_dir='models'):
    """Save the trained model and metadata"""
    os.makedirs(output_dir, exist_ok=True)
    
    # Save model
    model_file = os.path.join(output_dir, 'delivery_time_model.joblib')
    joblib.dump(model, model_file)
    print(f"\nModel saved to: {model_file}")
    
    # Save label encoders
    encoders_file = os.path.join(output_dir, 'label_encoders.joblib')
    joblib.dump(label_encoders, encoders_file)
    print(f"Label encoders saved to: {encoders_file}")
    
    # Save metadata
    metadata = {
        'model_type': model_type,
        'feature_columns': feature_cols,
        'metrics': metrics,
        'categorical_columns': list(label_encoders.keys())
    }
    
    metadata_file = os.path.join(output_dir, 'model_metadata.json')
    with open(metadata_file, 'w') as f:
        json.dump(metadata, f, indent=2)
    print(f"Metadata saved to: {metadata_file}")

if __name__ == '__main__':
    print("=" * 60)
    print("Delivery Time Prediction Model Training")
    print("=" * 60)
    
    # Load data
    print("\n1. Loading dataset...")
    df = load_data()
    print(f"   Loaded {len(df)} samples")
    
    # Prepare features
    print("\n2. Preparing features...")
    X, y, label_encoders, feature_cols = prepare_features(df)
    print(f"   Features: {', '.join(feature_cols)}")
    print(f"   Target: delivery_time_minutes")
    
    # Train models
    print("\n3. Training models...")
    model, model_type, metrics = train_models(X, y)
    
    # Save model
    print("\n4. Saving model...")
    save_model(model, model_type, label_encoders, feature_cols, metrics)
    
    print("\n" + "=" * 60)
    print("Training completed successfully!")
    print("=" * 60)


