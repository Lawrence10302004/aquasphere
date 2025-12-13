#!/bin/bash
# Setup script for ML delivery prediction system

echo "=========================================="
echo "AquaSphere ML Setup Script"
echo "=========================================="
echo ""

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    if ! command -v python &> /dev/null; then
        echo "ERROR: Python is not installed. Please install Python 3.7 or higher."
        exit 1
    else
        PYTHON_CMD="python"
    fi
else
    PYTHON_CMD="python3"
fi

echo "✓ Python found: $PYTHON_CMD"
echo ""

# Install dependencies
echo "1. Installing Python dependencies..."
$PYTHON_CMD -m pip install -r requirements.txt
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install dependencies"
    exit 1
fi
echo "✓ Dependencies installed"
echo ""

# Generate synthetic data
echo "2. Generating synthetic dataset..."
$PYTHON_CMD generate_synthetic_data.py
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to generate synthetic data"
    exit 1
fi
echo "✓ Synthetic dataset generated"
echo ""

# Train model
echo "3. Training ML model..."
$PYTHON_CMD train_model.py
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to train model"
    exit 1
fi
echo "✓ Model trained and saved"
echo ""

echo "=========================================="
echo "Setup completed successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test the prediction: python predict.py '{\"latitude\": 14.1494, \"longitude\": 121.3156, \"municipality\": \"Calauan\", \"barangay\": \"San Isidro\", \"postal_code\": \"4012\", \"time_of_order\": 14, \"day_of_week\": 2, \"order_size\": 10}'"
echo "2. The PHP endpoint is ready at: api/predict_delivery.php"
echo ""


