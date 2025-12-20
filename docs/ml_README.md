# Machine Learning Delivery Prediction System

This directory contains the machine learning system for predicting delivery time and calculating shipping fees for the AquaSphere water delivery system.

## Overview

The ML system predicts:
- **Estimated delivery time** (in minutes) based on delivery location and order details
- **Shipping fee** (in PHP) calculated from the predicted delivery time

## Features Used

The model uses the following features:
- `distance_km`: Distance from delivery hub (San Pablo City) to delivery location
- `latitude`, `longitude`: Delivery coordinates
- `municipality`: Municipality name (categorical)
- `barangay`: Barangay name (categorical)
- `postal_code`: Postal code (categorical)
- `time_of_order`: Hour of order (0-23)
- `day_of_week`: Day of week (0=Monday, 6=Sunday)
- `order_size`: Number of water bottles

## Setup Instructions

### 1. Install Python Dependencies

```bash
pip install -r requirements.txt
```

Or if using Python 3 specifically:
```bash
pip3 install -r requirements.txt
```

### 2. Generate Synthetic Dataset

```bash
python generate_synthetic_data.py
```

This will create `synthetic_delivery_data.csv` with 5000 synthetic delivery records.

### 3. Train the Model

```bash
python train_model.py
```

This will:
- Load the synthetic dataset
- Train both Linear Regression and Random Forest models
- Select the best performing model
- Save the model to `models/` directory

The trained model files will be saved in the `models/` directory:
- `delivery_time_model.joblib`: Trained model
- `label_encoders.joblib`: Label encoders for categorical features
- `model_metadata.json`: Model metadata and configuration

### 4. Test Prediction

You can test the prediction script directly:

```bash
python predict.py '{"latitude": 14.1494, "longitude": 121.3156, "municipality": "Calauan", "barangay": "San Isidro", "postal_code": "4012", "time_of_order": 14, "day_of_week": 2, "order_size": 10}'
```

Or pipe JSON input:
```bash
echo '{"latitude": 14.1494, "longitude": 121.3156, "municipality": "Calauan", "barangay": "San Isidro", "postal_code": "4012", "time_of_order": 14, "day_of_week": 2, "order_size": 10}' | python predict.py
```

## PHP Integration

The PHP endpoint `api/predict_delivery.php` can be called to get predictions in real-time.

### API Endpoint

**URL:** `/api/predict_delivery.php`

**Method:** POST

**Request Body:**
```json
{
    "latitude": 14.1494,
    "longitude": 121.3156,
    "municipality": "Calauan",
    "barangay": "San Isidro",
    "postal_code": "4012",
    "order_size": 10,
    "time_of_order": 14,  // Optional, defaults to current hour
    "day_of_week": 2      // Optional, defaults to current day
}
```

**Response:**
```json
{
    "success": true,
    "delivery_time_minutes": 45.5,
    "shipping_fee": 72.75,
    "delivery_time_hours": 0.76
}
```

### Example PHP Usage

```php
$data = [
    'latitude' => 14.1494,
    'longitude' => 121.3156,
    'municipality' => 'Calauan',
    'barangay' => 'San Isidro',
    'postal_code' => '4012',
    'order_size' => 10
];

$ch = curl_init('http://your-domain.com/api/predict_delivery.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Delivery Time: " . $result['delivery_time_minutes'] . " minutes\n";
    echo "Shipping Fee: PHP " . $result['shipping_fee'] . "\n";
}
```

### Example JavaScript Usage

```javascript
async function getDeliveryPrediction(deliveryAddress, orderSize) {
    const response = await fetch('/api/predict_delivery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            latitude: deliveryAddress.latitude,
            longitude: deliveryAddress.longitude,
            municipality: deliveryAddress.city,
            barangay: deliveryAddress.barangay,
            postal_code: deliveryAddress.postalCode,
            order_size: orderSize
        })
    });
    
    const result = await response.json();
    return result;
}
```

## Model Performance

The training script will output model performance metrics:
- **MAE (Mean Absolute Error)**: Average prediction error in minutes
- **RMSE (Root Mean Squared Error)**: Penalizes larger errors more
- **R² Score**: Proportion of variance explained (closer to 1.0 is better)

The system automatically selects the best performing model (Linear Regression or Random Forest).

## Shipping Fee Calculation

Shipping fee is calculated using:
```
shipping_fee = BASE_FEE + (delivery_time_minutes × RATE_PER_MINUTE)
```

Where:
- `BASE_FEE = 50.0 PHP`
- `RATE_PER_MINUTE = 0.5 PHP`

These constants can be adjusted in `predict.py`.

## Notes

- The system uses synthetic data for training. For production, retrain with real delivery data when available.
- The model includes a fallback calculation if the Python script fails to execute.
- Ensure Python 3 is installed and accessible from PHP (check `python` or `python3` command).
- On Windows, the system will try `python` command; on Linux/Mac, it will try `python3`.

## Troubleshooting

1. **Python not found**: Ensure Python is installed and in your system PATH
2. **Model file not found**: Run `train_model.py` first to generate the model
3. **Permission errors**: Ensure PHP has execute permissions for Python scripts
4. **Import errors**: Install all dependencies with `pip install -r requirements.txt`


