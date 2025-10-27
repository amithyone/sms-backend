# Google OAuth Setup Guide

## 📋 Complete Implementation Guide

### Step 1: Get Google OAuth Credentials

1. **Go to Google Cloud Console:**
   - Visit: https://console.cloud.google.com/
   - Create a new project: "FADSMS"

2. **Enable APIs:**
   - Go to "APIs & Services" > "Library"
   - Search and enable: "Google+ API"

3. **Create OAuth 2.0 Credentials:**
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client ID"
   - Application type: **Web application**
   - Name: `FADSMS OAuth Client`
   
   **Authorized JavaScript origins:**
   ```
   https://fadsms.com
   http://localhost:5173
   ```
   
   **Authorized redirect URIs:**
   ```
   https://api.fadsms.com/auth/google/callback
   http://localhost:8000/auth/google/callback
   ```

4. **Copy Credentials:**
   - You'll get a **Client ID** (looks like: `123456789-abc.apps.googleusercontent.com`)
   - You'll get a **Client Secret** (looks like: `GOCSPX-abc123xyz`)
   - Save these for the next step

---

### Step 2: Configure Laravel Backend

1. **Add to `.env` file:**
```env
GOOGLE_CLIENT_ID=your-google-client-id-here
GOOGLE_CLIENT_SECRET=your-google-client-secret-here
GOOGLE_REDIRECT_URI=https://api.fadsms.com/auth/google/callback
```

2. **Run database migration:**
```bash
cd /var/www/api.fadsms.com
php artisan migrate
```

---

### Step 3: Test the Implementation

#### Backend Test:
1. Visit: `https://api.fadsms.com/auth/google`
2. Should redirect to Google sign-in
3. After signing in, should redirect back with user data

#### Frontend Test:
1. Go to `https://fadsms.com`
2. Click "Sign in with Google" button
3. Complete Google authentication
4. Should log you in automatically

---

### Step 4: Security Checklist

- [x] Laravel Socialite installed
- [ ] Google OAuth credentials added to .env
- [ ] Database migration run
- [ ] Routes configured
- [ ] Controller created
- [ ] Frontend button added
- [ ] CORS configured properly
- [ ] HTTPS enforced in production

---

## 🔧 Troubleshooting

### "redirect_uri_mismatch" Error:
- Check that redirect URI in Google Console EXACTLY matches the one in your .env
- Make sure there are no trailing slashes
- Verify the protocol (http vs https)

### "invalid_client" Error:
- Double-check your GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env
- Make sure there are no extra spaces
- Verify the credentials are from the correct Google project

### User Can't Login:
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify database connection
- Check if user email already exists with different auth method

---

## 📚 Documentation

- Laravel Socialite: https://laravel.com/docs/10.x/socialite
- Google OAuth 2.0: https://developers.google.com/identity/protocols/oauth2
- Google Cloud Console: https://console.cloud.google.com/

---

## ✅ After Setup

Once everything is configured:
1. Test login/signup with a Google account
2. Verify user is created in database
3. Check that authentication token is returned
4. Test logout and re-login

---

**Next Steps:**
1. Add your Google OAuth credentials to `.env`
2. Run the migration
3. Test the Google login flow
4. Deploy to production

