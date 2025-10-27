# Email Configuration Instructions for FADDED SMS

## Current Configuration
- **From Email:** help@faddedsms.com
- **From Name:** FADDED SMS
- **SMTP Host:** smtp.gmail.com (Gmail)
- **Port:** 587 (TLS)

## Required: Set Up App Password

Since you're using Gmail, you need to create an **App-Specific Password**:

### Steps to Create Gmail App Password:

1. **Go to Google Account Settings:**
   - Visit: https://myaccount.google.com/security

2. **Enable 2-Step Verification** (if not already enabled):
   - Under "Signing in to Google"
   - Click "2-Step Verification"
   - Follow the setup process

3. **Create App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Or search for "App passwords" in your Google Account settings
   - Select "Mail" as the app
   - Select "Other (Custom name)" as the device
   - Name it: "FADDED SMS Server"
   - Click "Generate"
   - Copy the 16-character password (it will look like: `xxxx xxxx xxxx xxxx`)

4. **Update .env File:**
   - Open `/var/www/api.fadsms.com/.env`
   - Find the line: `MAIL_PASSWORD=`
   - Add your app password (remove spaces): `MAIL_PASSWORD=xxxxxxxxxxxxxxxx`
   - Save the file

5. **Clear Config Cache:**
   ```bash
   cd /var/www/api.fadsms.com
   php artisan config:clear
   ```

## Alternative: Use a Different Email Service

If you prefer not to use Gmail, you can use other SMTP services:

### Using SendGrid:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="help@faddedsms.com"
MAIL_FROM_NAME="FADDED SMS"
```

### Using Mailgun:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="help@faddedsms.com"
MAIL_FROM_NAME="FADDED SMS"
```

### Using Your Domain's SMTP:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.faddedsms.com
MAIL_PORT=587
MAIL_USERNAME=help@faddedsms.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="help@faddedsms.com"
MAIL_FROM_NAME="FADDED SMS"
```

## Testing

After configuring the password, run this command to send a test email:

```bash
cd /var/www/api.fadsms.com
php artisan tinker

# Then run:
Mail::raw('Test email from FADDED SMS!', function($message) {
    $message->to('imax9ja@gmail.com')->subject('Test Email');
});
```

## Email Features Configured

✅ Password Reset Emails
✅ Welcome/Registration Emails  
✅ Verification Emails
✅ Notification Emails

All emails will be sent from: **help@faddedsms.com**

