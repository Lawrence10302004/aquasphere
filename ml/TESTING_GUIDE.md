# Testing Guide for ML Delivery Prediction System

This guide will walk you through testing the ML system step by step.

## Prerequisites

1. **Python 3.7 or higher** installed
   - Check: `python --version` or `python3 --version`
   - If not installed, download from [python.org](https://www.python.org/downloads/)

2. **Command line access** (Terminal/PowerShell/CMD)

## Step 1: Navigate to ML Directory

Open your terminal/command prompt and navigate to the ML directory:

```bash
cd ml
```

## Step 2: Install Python Dependencies

Install required Python packages:

**Windows:**
```bash
python -m pip install -r requirements.txt
```

**Linux/Mac:**
```bash
python3 -m pip install -r requirements.txt
```

**Or use pip directly:**
```bash
pip install scikit-learn pandas numpy joblib
```

You should see packages being installed. Wait for it to complete.

## Step 3: Generate Synthetic Dataset

Generate the training data:

**Windows:**
```bash
python generate_synthetic_data.py
```

**Linux/Mac:**
```bash
python3 generate_synthetic_data.py
```

**Expected Output:**
```
Generating synthetic delivery dataset...
Dataset generated: 5000 samples
Saved to: synthetic_delivery_data.csv

Dataset preview:
[Shows first 10 rows of data]

Dataset statistics:
[Shows statistical summary]
```

This creates `synthetic_delivery_data.csv` in the `ml/` directory.

## Step 4: Train the Model

Train the ML model:

**Windows:**
```bash
python train_model.py
```

**Linux/Mac:**
```bash
python3 train_model.py
```

**Expected Output:**
```
============================================================
Delivery Time Prediction Model Training
============================================================

1. Loading dataset...
   Loaded 5000 samples

2. Preparing features...
   Features: distance_km, latitude, longitude, ...

3. Training models...
Training Linear Regression model...
Linear Regression - MAE: X.XX, RMSE: X.XX, R²: X.XXXX

Training Random Forest model...
Random Forest - MAE: X.XX, RMSE: X.XX, R²: X.XXXX

[Shows which model performs better]

4. Saving model...
Model saved to: models/delivery_time_model.joblib
Label encoders saved to: models/label_encoders.joblib
Metadata saved to: models/model_metadata.json

============================================================
Training completed successfully!
============================================================
```

This creates the `models/` directory with trained model files.

## Step 5: Test Predictions (Python Script)

Test the prediction system with sample data:

**Windows:**
```bash
python test_prediction.py
```

**Linux/Mac:**
```bash
python3 test_prediction.py
```

**Expected Output:**
```
============================================================
Testing Delivery Prediction System
============================================================

Test 1: Calauan (Close)
------------------------------------------------------------
  Location: Calauan, San Isidro
  Coordinates: (14.1494, 121.3156)
  Order Size: 10 bottles
  Time of Order: 14:00
  Day of Week: 2 (0=Mon, 6=Sun)
  → Delivery Time: XX.XX minutes (X.XX hours)
  → Shipping Fee: PHP XX.XX

[More test cases...]

============================================================
Test completed!
============================================================
```

## Step 6: Test Individual Predictions

Test a single prediction manually:

**Windows:**
```bash
python predict.py "{\"latitude\": 14.1494, \"longitude\": 121.3156, \"municipality\": \"Calauan\", \"barangay\": \"San Isidro\", \"postal_code\": \"4012\", \"time_of_order\": 14, \"day_of_week\": 2, \"order_size\": 10}"
```

**Linux/Mac:**
```bash
python3 predict.py '{"latitude": 14.1494, "longitude": 121.3156, "municipality": "Calauan", "barangay": "San Isidro", "postal_code": "4012", "time_of_order": 14, "day_of_week": 2, "order_size": 10}'
```

**Expected Output:**
```json
{"success": true, "delivery_time_minutes": 45.5, "shipping_fee": 72.75, "delivery_time_hours": 0.76}
```

## Step 7: Test PHP Endpoint (Optional)

If you want to test the PHP endpoint, you need a web server running. You can:

### Option A: Use PHP Built-in Server

1. Open a new terminal/command prompt
2. Navigate to your project root:
   ```bash
   cd C:\Users\USER\Documents\finalprojectvd
   ```
3. Start PHP server:
   ```bash
   php -S localhost:8000
   ```

4. Test with curl (in another terminal):
   ```bash
   curl -X POST http://localhost:8000/api/predict_delivery.php -H "Content-Type: application/json" -d "{\"latitude\": 14.1494, \"longitude\": 121.3156, \"municipality\": \"Calauan\", \"barangay\": \"San Isidro\", \"postal_code\": \"4012\", \"order_size\": 10}"
   ```

### Option B: Test via Browser Console

1. Open your application in browser
2. Open Developer Console (F12)
3. Run this JavaScript:
   ```javascript
   fetch('/api/predict_delivery.php', {
       method: 'POST',
       headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({
           latitude: 14.1494,
           longitude: 121.3156,
           municipality: 'Calauan',
           barangay: 'San Isidro',
           postal_code: '4012',
           order_size: 10
       })
   })
   .then(r => r.json())
   .then(console.log);
   ```

## Troubleshooting

### Issue: "Python is not recognized"
**Solution:** 
- Make sure Python is installed and added to PATH
- Try `python3` instead of `python` (Linux/Mac)
- Use full path: `C:\Python39\python.exe` (Windows)

### Issue: "Module not found" errors
**Solution:**
- Make sure you're in the `ml/` directory
- Reinstall dependencies: `pip install -r requirements.txt`

### Issue: "Model file not found"
**Solution:**
- Make sure you ran `train_model.py` first
- Check that `models/` directory exists with model files

### Issue: PHP endpoint returns error
**Solution:**
- Check that Python is accessible from PHP
- Check PHP error logs
- Verify model files exist in `ml/models/` directory
- Test Python script directly first (Step 6)

### Issue: Permission denied (Linux/Mac)
**Solution:**
- Make scripts executable: `chmod +x *.py`
- Or run with: `python3 script.py` instead of `./script.py`

## Quick Test Checklist

- [ ] Python installed and working
- [ ] Dependencies installed (`pip install -r requirements.txt`)
- [ ] Synthetic data generated (`synthetic_delivery_data.csv` exists)
- [ ] Model trained (`models/` directory exists with files)
- [ ] Test script runs successfully (`test_prediction.py`)
- [ ] Individual prediction works (`predict.py`)
- [ ] PHP endpoint works (if testing web integration)

## Next Steps

Once testing is successful:
1. Integrate into your cart/checkout pages
2. Call the PHP endpoint when user selects delivery address
3. Display predicted delivery time and shipping fee
4. Use the values in order creation


