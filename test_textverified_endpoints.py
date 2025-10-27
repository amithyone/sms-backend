#!/usr/bin/env python3
import requests
import json

# Test the actual API endpoints that the Python client might be using
api_key = "AOlwncA1BJhofCVZ6sUEYIJ1wgst9tyd1zsodaerhVin4Ev1LxqxQPlFXHeLDm"
username = "faddedog@gmail.com"

def test_direct_api():
    """Test direct API calls to see what endpoints work"""
    
    # Test different possible endpoints
    endpoints = [
        "https://www.textverified.com/api/v2/account/me",
        "https://www.textverified.com/api/v2/services",
        "https://api.textverified.com/v2/account/me",
        "https://api.textverified.com/v2/services",
        "https://www.textverified.com/api/account/me",
        "https://www.textverified.com/api/services",
    ]
    
    headers = {
        'X-API-KEY': api_key,
        'X-API-USERNAME': username,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
    
    for endpoint in endpoints:
        try:
            print(f"Testing: {endpoint}")
            response = requests.get(endpoint, headers=headers, timeout=10)
            print(f"  Status: {response.status_code}")
            if response.status_code == 200:
                print(f"  Success! Response: {response.text[:200]}...")
                return endpoint
            else:
                print(f"  Failed: {response.text[:100]}...")
        except Exception as e:
            print(f"  Error: {e}")
        print()
    
    return None

if __name__ == "__main__":
    print("Testing TextVerified API endpoints...")
    working_endpoint = test_direct_api()
    if working_endpoint:
        print(f"✅ Found working endpoint: {working_endpoint}")
    else:
        print("❌ No working endpoints found")
