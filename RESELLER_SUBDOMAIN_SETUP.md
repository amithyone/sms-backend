# Reseller Panel Subdomain & Custom Domain Setup

## Overview
When a reseller panel is approved, the system automatically:
1. Creates Nginx configuration for the subdomain
2. Enables the subdomain
3. Obtains SSL certificate via Let's Encrypt
4. Configures custom domain (if provided)
5. Sends DNS instructions to the user

## Automated Subdomain Setup

### What Happens When You Approve a Panel:

1. **Nginx Configuration Created**
   - File: `/etc/nginx/sites-available/{subdomain}.fadsms.com`
   - Points to: `/var/www/fadsms.com` (same React app)
   - Symlinked to: `/etc/nginx/sites-enabled/{subdomain}.fadsms.com`

2. **SSL Certificate**
   - Automatically obtained via Certbot
   - HTTPS enabled immediately
   - Auto-renewal configured

3. **Testing**
   - Nginx configuration tested before applying
   - Rollback on failure

## Custom Domain Setup

### For Subdomains (e.g., test.fadsms.com):
✅ **Fully Automated** - Works immediately after approval

### For Custom Domains (e.g., sms.clientbrand.com):
⚠️ **Requires DNS Configuration by Client**

The system will:
1. Create Nginx configuration
2. Send DNS instructions to user's inbox
3. Wait for DNS propagation (up to 48 hours)
4. Attempt SSL certificate after DNS is live

### DNS Instructions Sent to Users:

When a custom domain is configured, users receive:

```
📋 Custom Domain Setup:
To activate your custom domain (sms.clientbrand.com), add these DNS records:

• A Record: @ → [SERVER_IP]
• A Record: www → [SERVER_IP]
• Alternative: CNAME: sms.clientbrand.com → fadsms.com

DNS changes may take up to 48 hours to propagate.
```

## Server Configuration

### Permissions Setup:
```bash
# Sudoers file created at: /etc/sudoers.d/nginx-reseller
www-data ALL=(ALL) NOPASSWD: /usr/sbin/nginx -t
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot

# Directory permissions
chown -R www-data:www-data /etc/nginx/sites-available /etc/nginx/sites-enabled
```

## File Structure

### Service File:
- `/var/www/api.fadsms.com/app/Services/SubdomainService.php`
  - `createSubdomain()` - Creates subdomain configuration
  - `configureCustomDomain()` - Sets up custom domain
  - `obtainSSLCertificate()` - Gets SSL via Certbot
  - `getDNSInstructions()` - Returns DNS setup info
  - `removeSubdomain()` - Cleans up on cancellation

### Controller:
- `/var/www/api.fadsms.com/app/Http/Controllers/ResellerPanelController.php`
  - `approve()` - Calls SubdomainService on approval
  - `getDNSInstructions()` - API endpoint for DNS info

## Testing a New Panel

1. **Create Panel** (as user):
   - Navigate to Settings > Reseller Panel
   - Fill form with:
     - Panel Name
     - Subdomain (e.g., "test")
     - Custom Domain (optional, e.g., "sms.yourbrand.com")
     - Subscription Type
     - Brand Name

2. **Approve Panel** (as admin):
   - Go to `/admin/reseller-panels`
   - Click "✅ Approve" on pending panel
   - System will:
     - Create Nginx config for `test.fadsms.com`
     - Enable the subdomain
     - Obtain SSL certificate
     - Send notification to user

3. **Test Access**:
   - Visit `https://test.fadsms.com`
   - Should see the main site (white-label customization pending)
   - User receives inbox message with access details

4. **Custom Domain** (if configured):
   - User receives DNS instructions in inbox
   - User configures DNS at their provider
   - After DNS propagates, visit custom domain
   - System will automatically obtain SSL

## Troubleshooting

### Subdomain Not Working:
1. Check Nginx config: `sudo nginx -t`
2. Check if config exists: `ls -la /etc/nginx/sites-available/*.fadsms.com`
3. Check logs: `tail -f /var/log/nginx/error.log`
4. Manually reload: `sudo systemctl reload nginx`

### SSL Certificate Failed:
1. Check DNS resolves: `nslookup subdomain.fadsms.com`
2. Manually obtain: `sudo certbot --nginx -d subdomain.fadsms.com`
3. Check certbot logs: `cat /var/log/letsencrypt/letsencrypt.log`

### Custom Domain Issues:
1. Verify DNS propagation: `dig customdomain.com`
2. Check A record points to correct IP
3. Wait 24-48 hours for full propagation
4. Manually configure SSL after DNS is live

## API Endpoints

### Get DNS Instructions (User):
```bash
GET /api/reseller/dns-instructions
Authorization: Bearer {token}
```

Returns DNS configuration instructions for custom domain.

### Approve Panel (Admin):
```bash
POST /api/admin/reseller/{id}/approve
Authorization: Bearer {admin_token}
```

Automatically triggers subdomain creation.

## Next Steps

### White-Label Customization (Pending):
- Brand colors
- Logo replacement
- Custom footer text
- Hide/show platform branding

Once approved, subdomain should work immediately!
For custom domains, users need to configure DNS first.

