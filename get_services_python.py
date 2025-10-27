#!/usr/bin/env python3
from textverified import TextVerified
from textverified import NumberType, ReservationType
import json

client = TextVerified(
    api_key="AOlwncA1BJhofCVZ6sUEYIJ1wgst9tyd1zsodaerhVin4Ev1LxqxQPlFXHeLDm",
    api_username="faddedog@gmail.com",
)

try:
    services = client.services.list(
        number_type=NumberType.MOBILE,
        reservation_type=ReservationType.VERIFICATION
    )
    
    # Return first 10 services as JSON
    result = []
    for service in services[:10]:
        result.append({
            "service": service.service_name,
            "name": service.service_name,
            "cost": 0.0,  # Cost not available in this endpoint
            "count": 1,
            "provider": "textverified",
            "provider_name": "TextVerified"
        })
    
    print(json.dumps(result))
except Exception as e:
    print(json.dumps([]))
