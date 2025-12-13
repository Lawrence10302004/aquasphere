@echo off
REM Setup script for ML delivery prediction system (Windows)

echo ==========================================
echo AquaSphere ML Setup Script
echo ==========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed. Please install Python 3.7 or higher.
    exit /b 1
)

echo ^✓ Python found
echo.

REM Install dependencies
echo 1. Installing Python dependencies...
python -m pip install -r requirements.txt
if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    exit /b 1
)
echo ^✓ Dependencies installed
echo.

REM Generate synthetic data
echo 2. Generating synthetic dataset...
python generate_synthetic_data.py
if errorlevel 1 (
    echo ERROR: Failed to generate synthetic data
    exit /b 1
)
echo ^✓ Synthetic dataset generated
echo.

REM Train model
echo 3. Training ML model...
python train_model.py
if errorlevel 1 (
    echo ERROR: Failed to train model
    exit /b 1
)
echo ^✓ Model trained and saved
echo.

echo ==========================================
echo Setup completed successfully!
echo ==========================================
echo.
echo Next steps:
echo 1. Test the prediction: python predict.py "{\"latitude\": 14.1494, \"longitude\": 121.3156, \"municipality\": \"Calauan\", \"barangay\": \"San Isidro\", \"postal_code\": \"4012\", \"time_of_order\": 14, \"day_of_week\": 2, \"order_size\": 10}"
echo 2. The PHP endpoint is ready at: api/predict_delivery.php
echo.

pause


