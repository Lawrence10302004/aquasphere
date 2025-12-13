"""
Generate Synthetic Delivery Dataset
Creates realistic delivery data for training ML models
"""

import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import random

# Delivery hub location (San Pablo City, Laguna)
HUB_LATITUDE = 14.0703
HUB_LONGITUDE = 121.3253

# Laguna municipalities and their approximate coordinates
LAGUNA_MUNICIPALITIES = {
    'San Pablo City': {'lat': 14.0703, 'lng': 121.3253, 'postal': '4000'},
    'Calauan': {'lat': 14.1494, 'lng': 121.3156, 'postal': '4012'},
    'Alaminos': {'lat': 14.0631, 'lng': 121.2464, 'postal': '4001'},
    'Bay': {'lat': 14.1833, 'lng': 121.2833, 'postal': '4033'},
    'Biñan': {'lat': 14.3333, 'lng': 121.0833, 'postal': '4024'},
    'Cabuyao': {'lat': 14.2833, 'lng': 121.1167, 'postal': '4025'},
    'Calamba': {'lat': 14.2117, 'lng': 121.1653, 'postal': '4027'},
    'Los Baños': {'lat': 14.1667, 'lng': 121.2333, 'postal': '4030'},
    'Santa Cruz': {'lat': 14.2833, 'lng': 121.4167, 'postal': '4009'},
    'Pagsanjan': {'lat': 14.2667, 'lng': 121.4500, 'postal': '4008'},
    'Liliw': {'lat': 14.1333, 'lng': 121.4333, 'postal': '4004'},
    'Nagcarlan': {'lat': 14.1333, 'lng': 121.4167, 'postal': '4002'},
    'Rizal': {'lat': 14.1167, 'lng': 121.4000, 'postal': '4003'},
    'Majayjay': {'lat': 14.1500, 'lng': 121.4667, 'postal': '4005'},
    'Luisiana': {'lat': 14.1833, 'lng': 121.5167, 'postal': '4032'},
    'Paete': {'lat': 14.3667, 'lng': 121.4833, 'postal': '4016'},
    'Pila': {'lat': 14.2333, 'lng': 121.3667, 'postal': '4010'},
    'Victoria': {'lat': 14.2167, 'lng': 121.3167, 'postal': '4011'},
    'San Pedro': {'lat': 14.3583, 'lng': 121.0569, 'postal': '4023'},
    'Sta. Rosa': {'lat': 14.3167, 'lng': 121.1167, 'postal': '4026'},
}

# Sample barangays per municipality
BARANGAYS = {
    'San Pablo City': ['San Rafael', 'San Jose', 'San Roque', 'San Isidro', 'San Antonio'],
    'Calauan': ['San Isidro', 'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4'],
    'Alaminos': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Bay': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Biñan': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Cabuyao': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Calamba': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Los Baños': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Santa Cruz': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Pagsanjan': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Liliw': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Nagcarlan': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Rizal': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Majayjay': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Luisiana': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Paete': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Pila': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Victoria': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'San Pedro': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
    'Sta. Rosa': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
}

def haversine_distance(lat1, lon1, lat2, lon2):
    """
    Calculate the great circle distance between two points
    on the earth (specified in decimal degrees)
    Returns distance in kilometers
    """
    from math import radians, cos, sin, asin, sqrt
    
    # Convert decimal degrees to radians
    lat1, lon1, lat2, lon2 = map(radians, [lat1, lon1, lat2, lon2])
    
    # Haversine formula
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * asin(sqrt(a))
    
    # Radius of earth in kilometers
    r = 6371
    
    return c * r

def generate_delivery_time(distance_km, order_size, hour, day_of_week):
    """
    Generate realistic delivery time using formula:
    base_time + (distance_km × minutes_per_km) + traffic_factor + (order_size × size_factor)
    """
    # Base time in minutes
    base_time = 15
    
    # Minutes per kilometer (average speed ~30-40 km/h)
    minutes_per_km = 2.5
    
    # Traffic factor based on time of day
    if 7 <= hour <= 9 or 17 <= hour <= 19:  # Rush hours
        traffic_factor = random.uniform(10, 20)
    elif 10 <= hour <= 16:  # Normal hours
        traffic_factor = random.uniform(5, 10)
    else:  # Off-peak hours
        traffic_factor = random.uniform(0, 5)
    
    # Weekend traffic is generally lighter
    if day_of_week >= 5:  # Saturday (5) or Sunday (6)
        traffic_factor *= 0.7
    
    # Size factor (larger orders take more time to load/unload)
    size_factor = 0.5
    size_time = order_size * size_factor
    
    # Calculate delivery time
    delivery_time = base_time + (distance_km * minutes_per_km) + traffic_factor + size_time
    
    # Add some random variation (±10%)
    variation = random.uniform(-0.1, 0.1)
    delivery_time = delivery_time * (1 + variation)
    
    return max(20, round(delivery_time, 2))  # Minimum 20 minutes

def generate_synthetic_data(num_samples=5000):
    """Generate synthetic delivery dataset"""
    
    data = []
    random.seed(42)
    np.random.seed(42)
    
    for i in range(num_samples):
        # Randomly select municipality
        municipality = random.choice(list(LAGUNA_MUNICIPALITIES.keys()))
        muni_data = LAGUNA_MUNICIPALITIES[municipality]
        
        # Add small random variation to coordinates (within municipality)
        lat = muni_data['lat'] + random.uniform(-0.05, 0.05)
        lng = muni_data['lng'] + random.uniform(-0.05, 0.05)
        
        # Select barangay
        barangay = random.choice(BARANGAYS[municipality])
        
        # Calculate distance from hub
        distance_km = haversine_distance(HUB_LATITUDE, HUB_LONGITUDE, lat, lng)
        
        # Random order size (1-50 water bottles)
        order_size = random.randint(1, 50)
        
        # Random time of order (0-23 hours)
        hour = random.randint(0, 23)
        
        # Random day of week (0=Monday, 6=Sunday)
        day_of_week = random.randint(0, 6)
        
        # Generate delivery time
        delivery_time_minutes = generate_delivery_time(distance_km, order_size, hour, day_of_week)
        
        # Store data
        data.append({
            'distance_km': round(distance_km, 2),
            'latitude': round(lat, 6),
            'longitude': round(lng, 6),
            'municipality': municipality,
            'barangay': barangay,
            'postal_code': muni_data['postal'],
            'time_of_order': hour,
            'day_of_week': day_of_week,
            'order_size': order_size,
            'delivery_time_minutes': delivery_time_minutes
        })
    
    df = pd.DataFrame(data)
    return df

if __name__ == '__main__':
    print("Generating synthetic delivery dataset...")
    df = generate_synthetic_data(num_samples=5000)
    
    # Save to CSV
    output_file = 'synthetic_delivery_data.csv'
    df.to_csv(output_file, index=False)
    print(f"Dataset generated: {len(df)} samples")
    print(f"Saved to: {output_file}")
    print("\nDataset preview:")
    print(df.head(10))
    print("\nDataset statistics:")
    print(df.describe())


