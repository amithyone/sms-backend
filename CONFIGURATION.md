# Configuration Guide

This document explains the centralized configuration structure for the SMS Backend application.

## Overview

All API URLs and service configurations have been moved from hardcoded values to environment variables and centralized configuration files. This makes the application more flexible, secure, and easier to manage across different environments.

## Environment Variables

### Core Application
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### CORS Configuration
```env
# Development origins
CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:3000

# Production origins
CORS_PRODUCTION_ORIGINS=https://fadsms.com,https://www.fadsms.com
```

### SMS Provider Services
```env
# 5Sim Service
5SIM_BASE_URL=https://5sim.net
5SIM_API_KEY=your_5sim_api_key_here

# Dassy SMS Service
DASSY_BASE_URL=https://api.dassy.com
DASSY_API_KEY=your_dassy_api_key_here

# Tiger SMS Service
TIGER_SMS_BASE_URL=https://api.tigersms.com
TIGER_SMS_API_KEY=your_tiger_sms_api_key_here
```

### VTU Services
```env
# VTU.ng Service
VTU_NG_BASE_URL=https://vtu.ng/wp-json
VTU_NG_USERNAME=your_username
VTU_NG_PASSWORD=your_password
VTU_NG_PIN=your_pin
VTU_NG_TOKEN_CACHE_MINUTES=10080

# iRecharge Service
IRECHARGE_BASE_URL=https://irecharge.com.ng/pwr_api_sandbox/
IRECHARGE_USERNAME=your_username
IRECHARGE_PASSWORD=your_password
```

### Proxy Services
```env
# Webshare Proxy Service
WEBSHARE_BASE_URL=https://proxy.webshare.io/api/v2
WEBSHARE_API_KEY=your_webshare_api_key_here
```

### Payment Services
```env
# PayVibe Service
PAYVIBE_BASE_URL=https://payvibeapi.six3tech.com/api/v1
PAYVIBE_PUBLIC_KEY=your_public_key_here
PAYVIBE_SECRET_KEY=your_secret_key_here
PAYVIBE_PRODUCT_IDENTIFIER=sms
```

## Configuration Files

### `config/services.php`
This is the main configuration file that centralizes all service configurations. It reads from environment variables and provides fallback defaults.

### `app/Services/ConfigurationService.php`
A helper service class that provides easy access to centralized configurations with methods like:
- `getSmsConfig($provider)`
- `getVtuConfig($provider)`
- `getProxyConfig($provider)`
- `getPaymentConfig($provider)`
- `isServiceConfigured($service, $provider)`

## Usage Examples

### In Service Classes
```php
use App\Services\ConfigurationService;

class MyService
{
    public function __construct()
    {
        $this->baseUrl = ConfigurationService::getServiceBaseUrl('sms', '5sim');
        $this->apiKey = ConfigurationService::getServiceApiKey('sms', '5sim');
    }
}
```

### Direct Configuration Access
```php
// Get SMS service configuration
$config = config('services.sms.5sim');

// Get VTU service configuration
$config = config('services.vtu.vtu_ng');

// Get proxy service configuration
$config = config('services.proxy.webshare');
```

## Benefits

1. **Environment Flexibility**: Easy to switch between development, staging, and production environments
2. **Security**: Sensitive information like API keys are stored in environment variables, not in code
3. **Maintainability**: All service configurations are centralized in one place
4. **Scalability**: Easy to add new services or modify existing ones
5. **Version Control**: Configuration files can be safely committed to version control without exposing secrets

## Migration Notes

If you're migrating from the old hardcoded configuration:

1. Copy the `.env.example` file to `.env`
2. Fill in your actual API keys and URLs
3. Update any custom service classes to use the new configuration structure
4. Test all services to ensure they're working with the new configuration

## Adding New Services

To add a new service:

1. Add the configuration to `.env`
2. Update `config/services.php` with the new service configuration
3. Use `ConfigurationService` or direct config access in your service classes
4. Update this documentation

## Troubleshooting

### Common Issues
- **Service not working**: Check if the environment variable is set correctly
- **Configuration not found**: Ensure the service is added to `config/services.php`
- **API errors**: Verify API keys and base URLs are correct

### Debugging
```php
// Check if a service is configured
if (ConfigurationService::isServiceConfigured('sms', '5sim')) {
    // Service is properly configured
}

// Get all configurations
$allConfigs = ConfigurationService::getAllConfigs();
dd($allConfigs);
```
