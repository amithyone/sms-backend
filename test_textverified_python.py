#!/usr/bin/env python3
from textverified import TextVerified
from textverified import NumberType, ReservationType, ReservationCapability, NewVerificationRequest

# Initialize client with your credentials
client = TextVerified(
    api_key="AOlwncA1BJhofCVZ6sUEYIJ1wgst9tyd1zsodaerhVin4Ev1LxqxQPlFXHeLDm",
    api_username="faddedog@gmail.com",
)

def get_account_details():
    """Get your current balance and username"""
    try:
        account_info = client.account.me()
        print(f"Username: {account_info.username}")
        print(f"Current balance: ${account_info.current_balance}")
        return account_info
    except Exception as e:
        print(f"Error getting account details: {e}")
        return None

def get_service_list():
    """Get a list of available services"""
    try:
        # Get available services for mobile numbers and verifications
        all_services = client.services.list(
            number_type=NumberType.MOBILE,
            reservation_type=ReservationType.VERIFICATION
        )
        
        print("Available services:")
        for service in all_services[:5]:  # Show first 5
            print(f"  {service.service_name} (Capability: {service.capability})")
        
        return all_services
    except Exception as e:
        print(f"Error getting services: {e}")
        return []

def test_textverified_connection():
    """Test TextVerified connection and basic functionality"""
    print("=== TextVerified Python Client Test ===\n")
    
    # 1. Get account details
    print("1. Getting account details...")
    account_info = get_account_details()
    if not account_info:
        print("Failed to get account details. Check your credentials.")
        return False
    
    print("\n====\n")
    
    # 2. Get service list
    print("2. Getting available services...")
    services_list = get_service_list()
    if not services_list:
        print("No services available")
        return False
    
    print(f"\nFound {len(services_list)} services available")
    print("\n====\n")
    
    print("✅ TextVerified Python client is working correctly!")
    print("✅ API credentials are valid")
    print("✅ Services are accessible")
    
    return True

if __name__ == "__main__":
    test_textverified_connection()
