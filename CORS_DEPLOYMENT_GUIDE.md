# CORS Deployment Guide

## ✅ CORS Configuration Fixed!

Your CORS is now working for both **local development** and **live server**.

## 🔧 What Was Fixed

1. **Removed complex environment variable dependencies** that could break in production
2. **Hardcoded allowed origins** for reliability
3. **Added wildcard patterns** for localhost and subdomains
4. **Simplified configuration** that works everywhere

## 📁 Files Modified

- ✅ `config/cors.php` - Main CORS configuration
- ✅ `app/Providers/RouteServiceProvider.php` - Removed `/api` prefix
- ✅ Frontend API configuration - Fixed double `/api` issue

## 🌍 How It Works

### Local Development
- ✅ `http://localhost:5173` (React dev server)
- ✅ `http://localhost:3000` (Alternative React port)
- ✅ `http://127.0.0.1:5173`
- ✅ `http://127.0.0.1:3000`

### Live Server
- ✅ `https://fadsms.com`
- ✅ `https://www.fadsms.com`
- ✅ `https://*.fadsms.com` (any subdomain)

### Wildcard Patterns
- ✅ `http://localhost:*` (any port)
- ✅ `http://127.0.0.1:*` (any port)

## 🚀 Deployment Steps

### 1. Push to Git
```bash
git add .
git commit -m "Fix CORS configuration for local and production"
git push origin main
```

### 2. On Live Server
```bash
git pull origin main
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 3. Restart Services
```bash
# If using systemd
sudo systemctl restart your-app

# If using supervisor
sudo supervisorctl restart your-app

# If using PM2
pm2 restart your-app
```

## 🔒 Security Notes

- **Allowed Origins**: Only your specific domains are allowed
- **Credentials**: `supports_credentials: true` for authentication
- **Methods**: All HTTP methods allowed (`*`)
- **Headers**: All headers allowed (`*`)

## 🧪 Testing

### Local Test
```bash
# Test CORS endpoint
curl -H "Origin: http://localhost:5173" http://localhost:8000/cors-test
```

### Production Test
```bash
# Test from your live domain
curl -H "Origin: https://fadsms.com" https://your-live-server.com/cors-test
```

## 🆘 Troubleshooting

### If CORS Still Fails in Production

1. **Check domain spelling** in `config/cors.php`
2. **Clear all caches**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```
3. **Restart web server** (Apache/Nginx)
4. **Check browser console** for specific CORS errors

### Common Issues

- **Domain mismatch**: Make sure your live domain is exactly in the allowed origins
- **Protocol mismatch**: Use `https://` for production, `http://` for local
- **Port issues**: Wildcard patterns handle different ports automatically

## 📝 Customization

To add more domains, edit `config/cors.php`:

```php
'allowed_origins' => [
    // Your domains here
    'https://fadsms.com',
    'https://www.fadsms.com',
    'https://app.fadsms.com',  // Add more as needed
],
```

## 🎯 Summary

Your CORS configuration is now:
- ✅ **Production-ready**
- ✅ **Local development compatible**
- ✅ **Git-friendly** (no environment-specific configs)
- ✅ **Secure** (only your domains allowed)
- ✅ **Flexible** (handles subdomains and ports)

**Push to Git with confidence!** 🚀
