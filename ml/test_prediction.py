"""
Quick test script for delivery prediction
Tests the prediction system with sample data
"""

import json
import sys
import os

# Add current directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from predict import predict_delivery_time, calculate_shipping_fee

def test_prediction():
    """Test prediction with sample data"""
    
    print("=" * 60)
    print("Testing Delivery Prediction System")
    print("=" * 60)
    print()
    
    # Test cases
    test_cases = [
        {
            'name': 'Calauan (Close)',
            'latitude': 14.1494,
            'longitude': 121.3156,
            'municipality': 'Calauan',
            'barangay': 'San Isidro',
            'postal_code': '4012',
            'time_of_order': 14,
            'day_of_week': 2,
            'order_size': 10
        },
        {
            'name': 'Biñan (Medium Distance)',
            'latitude': 14.3333,
            'longitude': 121.0833,
            'municipality': 'Biñan',
            'barangay': 'Barangay 1',
            'postal_code': '4024',
            'time_of_order': 9,
            'day_of_week': 0,
            'order_size': 25
        },
        {
            'name': 'Los Baños (Medium Distance)',
            'latitude': 14.1667,
            'longitude': 121.2333,
            'municipality': 'Los Baños',
            'barangay': 'Barangay 1',
            'postal_code': '4030',
            'time_of_order': 17,
            'day_of_week': 4,
            'order_size': 5
        }
    ]
    
    for i, test in enumerate(test_cases, 1):
        print(f"Test {i}: {test['name']}")
        print("-" * 60)
        
        try:
            # Predict delivery time
            delivery_time = predict_delivery_time(
                test['latitude'],
                test['longitude'],
                test['municipality'],
                test['barangay'],
                test['postal_code'],
                test['time_of_order'],
                test['day_of_week'],
                test['order_size']
            )
            
            # Calculate shipping fee
            shipping_fee = calculate_shipping_fee(delivery_time)
            
            print(f"  Location: {test['municipality']}, {test['barangay']}")
            print(f"  Coordinates: ({test['latitude']}, {test['longitude']})")
            print(f"  Order Size: {test['order_size']} bottles")
            print(f"  Time of Order: {test['time_of_order']}:00")
            print(f"  Day of Week: {test['day_of_week']} (0=Mon, 6=Sun)")
            print(f"  → Delivery Time: {delivery_time} minutes ({delivery_time/60:.2f} hours)")
            print(f"  → Shipping Fee: PHP {shipping_fee:.2f}")
            print()
            
        except Exception as e:
            print(f"  ERROR: {str(e)}")
            print()
    
    print("=" * 60)
    print("Test completed!")
    print("=" * 60)

if __name__ == '__main__':
    # Check if model exists
    if not os.path.exists('models/delivery_time_model.joblib'):
        print("ERROR: Model not found. Please run 'python train_model.py' first.")
        sys.exit(1)
    
    test_prediction()


