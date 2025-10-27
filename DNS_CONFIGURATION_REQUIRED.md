# ⚠️ EMAIL DELIVERY ISSUE - DNS Configuration Required

## Problem
Gmail (and other major email providers) are blocking emails from `help@faddedsms.com` because they require **SPF and DKIM authentication**.

**Error from Gmail:**
```
550-5.7.26 Your email has been blocked because the sender is unauthenticated.
550-5.7.26 Gmail requires all senders to authenticate with either SPF or DKIM.
```

## Solution

You need to add DNS records to your domain `faddedsms.com` to authenticate emails.

### Required DNS Records:

#### 1. SPF Record (TXT Record)
Add this TXT record to your domain:

**Host/Name:** `@` or `faddedsms.com`  
**Type:** `TXT`  
**Value:** `v=spf1 ip4:75.119.155.252 ~all`

#### 2. DKIM Record (TXT Record)
First, generate DKIM keys:
```bash
mkdir -p /etc/opendkim/keys/faddedsms.com
cd /etc/opendkim/keys/faddedsms.com
opendkim-genkey -s mail -d faddedsms.com
chown opendkim:opendkim mail.private
```

Then add this TXT record:

**Host/Name:** `mail._domainkey.faddedsms.com`  
**Type:** `TXT`  
**Value:** (Get the value from `/etc/opendkim/keys/faddedsms.com/mail.txt`)

#### 3. DMARC Record (TXT Record)
**Host/Name:** `_dmarc.faddedsms.com`  
**Type:** `TXT`  
**Value:** `v=DMARC1; p=none; rua=mailto:help@faddedsms.com`

---

## Alternative Solutions

### Option 1: Use a Third-Party Email Service (RECOMMENDED)

Use a professional email sending service that handles authentication:

#### A. SendGrid (Free tier: 100 emails/day)
1. Sign up at https://sendgrid.com
2. Get your API key
3. Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key_here
MAIL_ENCRYPTION=tls
```

#### B. Mailgun (Free tier: 5,000 emails/month)
1. Sign up at https://mailgun.com
2. Verify your domain
3. Get SMTP credentials
4. Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
```

#### C. Amazon SES (Very cheap, reliable)
1. Sign up at AWS
2. Verify faddedsms.com domain
3. Get SMTP credentials
4. Update `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your_ses_username
MAIL_PASSWORD=your_ses_password
MAIL_ENCRYPTION=tls
```

### Option 2: Use Gmail with App Password

Configure Gmail to send from help@faddedsms.com:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your_16_digit_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="help@faddedsms.com"
MAIL_FROM_NAME="FADDED SMS"
```

---

## Immediate Fix (Quick Test)

For testing purposes, you can temporarily use a service like Mailtrap or MailHog:

### Mailtrap (Email Testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

---

## Current Server IP
**Server IP:** 75.119.155.252  
**Hostname:** vmi2773426.contaboserver.net

This IP needs to be authorized in your domain's SPF record.

---

## Next Steps

1. **Quickest:** Use SendGrid/Mailgun (15 minutes setup)
2. **Best:** Configure DNS records for your domain (requires domain DNS access)
3. **Alternative:** Use Gmail with app password

Once you choose an option, I can help configure it!

