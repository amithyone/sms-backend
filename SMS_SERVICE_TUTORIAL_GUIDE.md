# 📱 Fadded VIP 🔆  SMS Service Tutorial Guide

Welcome to the complete tutorial guide for using the FaddedSMS service! This guide will walk you through everything you need to know to successfully use our SMS verification platform.

## 🎯 What is FaddedSMS?

FaddedSMS is a premium SMS verification service that provides virtual phone numbers for receiving SMS codes from various platforms like WhatsApp, Telegram, Facebook, Instagram, and hundreds of other services. Our platform supports multiple countries and uses advanced provider networks to ensure high delivery rates.

---

## 🚀 Getting Started

### 1. Account Setup

#### Creating Your Account
1. **Visit**: https://fadsms.com
2. **Click**: "Sign Up" or "Register"
3. **Fill in**: Your email, password, and basic information
4. **Verify**: Your email address
5. **Complete**: Your profile setup

#### Account Verification
- Verify your email address
- Complete your profile information
- Add a phone number for account recovery

### 2. Funding Your Account

#### Available Payment Methods
- **Bank Transfer**: Direct bank deposit
- **PayVibe**: Online payment gateway
- **Crypto**: Bitcoin and other cryptocurrencies
- **Admin Credit**: Contact support for manual funding

#### Minimum Balance Requirements
- **SMS Services**: Minimum ₦1,500 per order
- **Recommended**: Keep ₦5,000+ for smooth operations

---

## 📋 Understanding SMS Services

### What You Get
- **Virtual Phone Numbers**: Real phone numbers from various countries
- **SMS Reception**: Receive verification codes instantly
- **Multiple Providers**: 5sim, TigerSMS, Dassy, TextVerified, SMSPool
- **Global Coverage**: Numbers from 50+ countries
- **High Success Rate**: 95%+ delivery rate

### Service Categories
Our services are organized into categories with the **Fadded VIP 🔆** branding:

#### 🔥 Most Popular Services
- **WhatsApp** (`wa`) - Instant messaging
- **Telegram** (`tg`) - Secure messaging
- **Signal** (`bw`) - Privacy-focused messaging
- **Tinder** (`oi`) - Dating platform
- **Facebook** (`fb`) - Social media
- **Google** (`go`) - Google services
- **Gmail** - Email service
- **Payoneer** - Payment platform
- **TikTok** (`lf`) - Social video platform
- **LinkedIn** (`tn`) - Professional network

#### 📱 Social Media & Dating
- **Instagram** (`ig`)
- **Discord** (`ds`)
- **Bumble** (`mo`)
- **Snapchat** (`snap`)
- **Twitter** (`tw`)

#### 💼 Business & Finance
- **Amazon** (`am`)
- **Uber** (`ub`)
- **PayPal**
- **Netflix**
- **Spotify**
- **YouTube**

---

## 🌍 Country Selection

### Supported Countries
Our service supports numbers from major countries worldwide:

#### Top Priority Countries
1. **Nigeria** (NG) - Most popular
2. **United States** (US) - High demand
3. **United Kingdom** (UK) - European coverage
4. **Canada** (CA) - North American
5. **Germany** (DE) - European
6. **France** (FR) - European
7. **India** (IN) - Asian market
8. **Brazil** (BR) - South American

#### Complete Country List
Use our API to get the full list:
```bash
GET /api/sms/countries
```

---

## 🔧 How to Use the Service

### Method 1: Web Interface (Recommended for Beginners)

#### Step 1: Login and Check Balance
1. Go to https://fadsms.com
2. Login to your account
3. Check your balance (top-right corner)
4. Ensure you have sufficient funds (minimum ₦1,500)

#### Step 2: Select Service and Country
1. **Navigate** to "SMS Services" section
2. **Choose Country**: Select your preferred country
3. **Browse Services**: See available services for that country
4. **Filter by Popularity**: Services are sorted by demand and success rate

#### Step 3: Purchase SMS Number
1. **Click** on your desired service (e.g., WhatsApp)
2. **Review** the price and provider information
3. **Click** "Buy Now" or "Purchase"
4. **Confirm** the purchase

#### Step 4: Receive Your Number
1. **Wait** for order processing (usually instant)
2. **Copy** the phone number provided
3. **Use** this number on the target platform
4. **Wait** for SMS code to arrive

#### Step 5: Get Your SMS Code
1. **Go to** "My Orders" or "SMS Orders"
2. **Find** your active order
3. **Click** "Get SMS Code"
4. **Copy** the verification code
5. **Use** the code to complete verification

### Method 2: API Integration (For Developers)

#### API Authentication
```bash
# Include your API key in headers
curl -H "X-API-Key: fad_your_api_key_here" \
     -H "Content-Type: application/json" \
     https://api.fadsms.com/api/v1/balance
```

#### Purchase SMS Number
```bash
curl -X POST https://api.fadsms.com/api/v1/sms/order \
     -H "X-API-Key: fad_your_api_key_here" \
     -H "Content-Type: application/json" \
     -d '{
       "country_code": "NG",
       "service": "whatsapp",
       "provider": "tiger_sms"
     }'
```

#### Poll for SMS Code
```bash
curl -X POST https://api.fadsms.com/api/v1/sms/code \
     -H "X-API-Key: fad_your_api_key_here" \
     -H "Content-Type: application/json" \
     -d '{
       "order_id": "SMS_abc123xyz"
     }'
```

---

## ⚙️ Provider Selection

### Available Providers

#### 1. **5sim** (Global Coverage)
- **Best for**: International services
- **Success Rate**: 95%+
- **Coverage**: 50+ countries
- **Speed**: Fast delivery

#### 2. **TigerSMS** (Reliable)
- **Best for**: Popular services (WhatsApp, Telegram)
- **Success Rate**: 93%+
- **Coverage**: 30+ countries
- **Speed**: Very fast

#### 3. **Dassy** (Premium)
- **Best for**: High-demand services
- **Success Rate**: 97%+
- **Coverage**: 25+ countries
- **Speed**: Ultra-fast

#### 4. **TextVerified** (US Only)
- **Best for**: US-based services
- **Success Rate**: 98%+
- **Coverage**: United States only
- **Speed**: Instant

#### 5. **SMSPool** (Cost-Effective)
- **Best for**: Budget-conscious users
- **Success Rate**: 90%+
- **Coverage**: 40+ countries
- **Speed**: Good

### Auto vs Manual Mode

#### Auto Mode (Recommended)
- **System selects** the best provider automatically
- **Highest success rate** based on real-time data
- **Optimal pricing** across providers
- **Faster processing**

#### Manual Mode (Advanced Users)
- **You choose** the specific provider
- **More control** over selection
- **Useful for** specific requirements
- **May have** higher costs

---

## 💰 Pricing Structure

### Base Pricing
- **Minimum Price**: ₦1,500 per SMS order
- **Currency**: All prices in Nigerian Naira (NGN)
- **No Hidden Fees**: Transparent pricing

### Price Factors
1. **Service Popularity**: Popular services may cost more
2. **Country Demand**: High-demand countries cost more
3. **Provider Quality**: Premium providers cost more
4. **Success Rate**: Higher success rate = higher price

### Example Prices
- **WhatsApp (Nigeria)**: ₦1,500 - ₦2,500
- **Telegram (US)**: ₦2,000 - ₦3,000
- **Facebook (UK)**: ₦1,800 - ₦2,800
- **Google (Canada)**: ₦2,200 - ₦3,200

---

## 📊 Order Management

### Order Statuses

#### Active
- **Status**: SMS number is ready
- **Action**: Use the number for verification
- **Duration**: Usually 10-20 minutes

#### Completed
- **Status**: SMS code received
- **Action**: Code is available for use
- **Duration**: Permanent (saved in history)

#### Expired
- **Status**: Time limit exceeded
- **Action**: No code received in time
- **Refund**: Automatic refund to account

#### Cancelled
- **Status**: Order cancelled by user
- **Action**: Manual cancellation
- **Refund**: Immediate refund

### Order History
- **View All Orders**: Check your complete history
- **Filter by Status**: See only active, completed, or expired orders
- **Search by Service**: Find specific service orders
- **Export Data**: Download order history

---

## 🔔 Notifications & Inbox

### Inbox Messages
All SMS orders create inbox messages with the **Fadded VIP 🔆** branding:

#### Order Created
- **Title**: "Fadded VIP 🔆  SMS Order - [Service Name]"
- **Message**: "Your virtual number [phone] for [service] is ready. Waiting for SMS verification code to arrive."

#### SMS Received
- **Title**: "Fadded VIP 🔆  SMS Received - [Service Name]"
- **Message**: "SMS verification code received for [phone] ([service]). Code: [code]"

### Notification Settings
- **Email Notifications**: Get notified when SMS arrives
- **Browser Notifications**: Real-time browser alerts
- **Mobile Alerts**: Push notifications (if using mobile app)

---

## 🛡️ Security & Best Practices

### Account Security
1. **Strong Password**: Use a complex password
2. **2FA**: Enable two-factor authentication
3. **Secure Email**: Use a secure email address
4. **Regular Updates**: Keep your profile updated

### Service Usage
1. **Check Balance**: Always ensure sufficient funds
2. **Valid Numbers**: Use the provided numbers correctly
3. **Time Limits**: Complete verification within the time limit
4. **One-Time Use**: Each number is for single use only

### API Security
1. **Secure Keys**: Keep API keys private
2. **IP Whitelisting**: Restrict API access to specific IPs
3. **Rate Limiting**: Respect API rate limits
4. **Error Handling**: Implement proper error handling

---

## 🚨 Troubleshooting

### Common Issues

#### "Insufficient Balance"
- **Cause**: Account balance below minimum requirement
- **Solution**: Fund your account with minimum ₦1,500

#### "Service Not Available"
- **Cause**: Service temporarily unavailable in selected country
- **Solution**: Try different country or wait and retry

#### "SMS Code Not Received"
- **Cause**: Verification code not delivered
- **Solutions**:
  1. Wait up to 20 minutes
  2. Try refreshing the order
  3. Contact support if still no code

#### "Order Expired"
- **Cause**: Time limit exceeded without receiving SMS
- **Solution**: Automatic refund will be processed

#### "Invalid Phone Number"
- **Cause**: Incorrect number format used
- **Solution**: Use the exact number provided by the system

### Getting Help

#### Support Channels
1. **Help Desk**: https://fadsms.com/support
2. **Email**: support@fadsms.com
3. **Live Chat**: Available on website
4. **Documentation**: https://api.fadsms.com/docs

#### Before Contacting Support
1. **Check Balance**: Ensure sufficient funds
2. **Verify Order**: Check order status and details
3. **Try Different Provider**: Switch to manual mode
4. **Check Service Status**: Verify service availability

---

## 📈 Advanced Features

### Bulk Operations
- **Multiple Orders**: Purchase multiple numbers simultaneously
- **Batch Processing**: Process orders in batches
- **API Integration**: Use API for bulk operations

### Reseller Program
- **White-Label**: Create your own SMS panel
- **Custom Branding**: Use your own brand
- **Profit Margins**: Earn from reselling services
- **API Access**: Full API access for integration

### API Management
- **Multiple Keys**: Create multiple API keys
- **Permissions**: Set specific permissions per key
- **Rate Limits**: Configure rate limits
- **Usage Tracking**: Monitor API usage

---

## 📱 Mobile Experience

### Mobile-Optimized Interface
Our platform is optimized for mobile devices:

#### Mobile Features
- **Responsive Design**: Works on all screen sizes
- **Touch-Friendly**: Easy navigation on mobile
- **Fast Loading**: Optimized for mobile networks
- **Mobile Notifications**: Browser push notifications

#### Mobile Best Practices
1. **Use Mobile Browser**: Chrome, Safari, Firefox
2. **Enable Notifications**: Allow browser notifications
3. **Stable Connection**: Use stable internet connection
4. **Copy Numbers**: Use copy button for phone numbers

---

## 🔄 Refund Policy

### Automatic Refunds
- **Expired Orders**: Automatic refund if no SMS received
- **Failed Orders**: Refund for technical failures
- **Provider Issues**: Refund for provider problems

### Manual Refunds
- **Cancelled Orders**: Refund for user-cancelled orders
- **Service Issues**: Refund for service problems
- **Processing Time**: 1-3 business days

### Refund Conditions
- **Valid Reason**: Must have valid reason for refund
- **Time Limit**: Request within 24 hours of order
- **No Abuse**: Cannot abuse refund system
- **Account Status**: Account must be in good standing

---

## 📊 Analytics & Reports

### Order Statistics
- **Total Orders**: Track your total SMS orders
- **Success Rate**: Monitor your success rate
- **Spending**: Track your spending patterns
- **Popular Services**: See your most-used services

### Provider Performance
- **Success Rates**: Compare provider performance
- **Speed**: Track delivery speeds
- **Reliability**: Monitor provider reliability
- **Cost Analysis**: Compare costs across providers

### Export Options
- **CSV Export**: Download order data
- **PDF Reports**: Generate PDF reports
- **API Data**: Access data via API
- **Custom Reports**: Create custom reports

---

## 🎯 Use Cases

### Personal Use
- **Account Verification**: Verify personal accounts
- **Temporary Numbers**: Use for temporary registrations
- **Privacy Protection**: Protect your real number
- **Testing Services**: Test new platforms safely

### Business Use
- **Bulk Verification**: Verify multiple accounts
- **Customer Onboarding**: Help customers with verification
- **Testing Platforms**: Test verification flows
- **Development**: Test during development

### Developer Integration
- **API Integration**: Build SMS verification into apps
- **Automation**: Automate verification processes
- **White-Label**: Create branded verification service
- **Custom Solutions**: Build custom verification solutions

---

## 🚀 Getting Started Checklist

### ✅ Account Setup
- [ ] Create account at https://fadsms.com
- [ ] Verify email address
- [ ] Complete profile information
- [ ] Set up two-factor authentication

### ✅ Funding Account
- [ ] Choose payment method
- [ ] Fund account with minimum ₦5,000
- [ ] Verify payment received
- [ ] Check balance in dashboard

### ✅ First SMS Order
- [ ] Navigate to SMS Services
- [ ] Select country (start with Nigeria)
- [ ] Choose popular service (WhatsApp recommended)
- [ ] Purchase SMS number
- [ ] Use number for verification
- [ ] Retrieve SMS code
- [ ] Complete verification process

### ✅ Advanced Setup (Optional)
- [ ] Create API key for integration
- [ ] Set up webhook endpoints
- [ ] Configure notification preferences
- [ ] Explore bulk operations
- [ ] Review analytics and reports

---

## 📞 Support & Contact

### Getting Help
- **Documentation**: https://api.fadsms.com/docs
- **Help Center**: https://fadsms.com/help
- **Support Tickets**: Submit tickets through dashboard
- **Live Chat**: Available during business hours

### Contact Information
- **Email**: support@fadsms.com
- **Business Hours**: Monday - Friday, 9 AM - 6 PM WAT
- **Response Time**: Within 24 hours
- **Emergency**: Use live chat for urgent issues

### Community
- **Discord**: Join our Discord community
- **Telegram**: Follow our Telegram channel
- **Twitter**: Follow us for updates
- **Blog**: Read our latest articles

---

## 🔮 Future Features

### Coming Soon
- **Webhook Support**: Real-time notifications
- **Mobile App**: Native iOS and Android apps
- **More Providers**: Additional SMS providers
- **Voice Calls**: Voice verification support
- **Advanced Analytics**: Detailed reporting
- **Multi-Language**: Support for more languages

### Roadmap
- **Q1 2025**: Mobile apps and webhooks
- **Q2 2025**: Voice verification and more providers
- **Q3 2025**: Advanced analytics and reporting
- **Q4 2025**: AI-powered optimization

---

## 📝 Terms & Conditions

### Important Notes
1. **Service Usage**: SMS numbers are for verification purposes only
2. **One-Time Use**: Each number can only be used once
3. **Time Limits**: Orders expire if not used within time limit
4. **Refund Policy**: Refunds available under specific conditions
5. **Prohibited Uses**: Cannot be used for illegal activities

### Account Terms
- **Account Security**: You're responsible for account security
- **Payment Terms**: All payments are final unless refunded
- **Service Availability**: Services subject to availability
- **Price Changes**: Prices may change without notice

---

## 🎉 Congratulations!

You're now ready to use the Fadded VIP 🔆 SMS service! This comprehensive guide covers everything you need to know to get started and make the most of our platform.

### Quick Start Summary
1. **Create Account** → Fund Account → Select Service → Purchase Number → Receive SMS Code
2. **Use Auto Mode** for best results
3. **Keep sufficient balance** (minimum ₦5,000 recommended)
4. **Check inbox** for notifications
5. **Contact support** if you need help

### Pro Tips
- **Start with popular services** like WhatsApp for testing
- **Use Nigeria** as your first country (most reliable)
- **Enable notifications** for real-time updates
- **Keep orders active** by using numbers promptly
- **Monitor your balance** regularly

**Welcome to Fadded VIP 🔆 - Your premium SMS verification partner!** 🚀

---

*Last Updated: January 2025*
*Version: 1.0*
*For the latest updates, visit: https://fadsms.com*
