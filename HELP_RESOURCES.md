# FaddedSMS Help Resources

## 📖 Getting Started

### Quick Start Guides
- [How to Fund Your Wallet](./docs/fund-wallet.md)
- [How to Buy SMS Numbers](./docs/buy-sms.md)
- [How to Purchase Airtime](./docs/purchase-airtime.md)
- [How to Buy Data Bundles](./docs/buy-data.md)
- [How to Pay Electricity Bills](./docs/pay-electricity.md)

---

## 🔑 API Documentation

### For Developers & Resellers
- [Complete API Documentation](./API_DOCUMENTATION.md)
- [Quick Start Guide](./API_QUICK_START.md)
- [API Summary](./RESELLER_API_SUMMARY.md)

### API Endpoints
- SMS Services API
- VTU Services API
- Wallet & Transactions API
- Authentication & Keys

---

## ❓ Frequently Asked Questions (FAQ)

### Account & Wallet

**Q: How do I fund my wallet?**  
A: Go to Wallet → Top Up → Generate account number. Transfer to the provided account and your wallet updates automatically within 10-15 seconds.

**Q: Can I withdraw from my wallet?**  
A: Currently, wallet funds are for service purchases only (non-withdrawable).

**Q: How do I check my transaction history?**  
A: Go to Transactions tab to see all your purchases and wallet activity.

### SMS Services

**Q: How long does it take to receive SMS codes?**  
A: Usually 10-60 seconds. The system polls automatically every 2 seconds.

**Q: What if I don't receive the SMS code?**  
A: Wait up to 5 minutes. If no code arrives, you can request a refund or try a different provider.

**Q: Which SMS provider should I choose?**  
A: Tiger SMS has 200 countries, Dassy is good for USA, SMSPool has most services (1,167).

**Q: Can I use the same number twice?**  
A: No, numbers are temporary and expire after use.

### VTU Services

**Q: How long does electricity token delivery take?**  
A: Instant in most cases. If provider times out, token delivered to inbox within 5-10 minutes.

**Q: What if my electricity purchase shows "Processing"?**  
A: This means VTU.ng is still processing. Your token will be automatically delivered to your inbox when ready.

**Q: Can I get a refund for airtime/data?**  
A: No refunds after successful delivery. Only if transaction fails.

**Q: Do you support all electricity providers?**  
A: Yes, all major Nigerian DISCOs (Ikeja, Eko, Abuja, Ibadan, Kaduna, Port Harcourt, Jos, Kano).

### API & Integration

**Q: How do I get an API key?**  
A: Go to Settings → API Keys → Create New API Key. Save it securely!

**Q: What are the API rate limits?**  
A: Default: 60 requests/minute, 10,000 requests/day (customizable per key).

**Q: Can I use the API for my business?**  
A: Yes! Build SMS verification services, VTU reseller panels, or integrate into your apps.

**Q: Is there an API fee?**  
A: No! API access is free. You only pay for actual services (SMS, airtime, etc.) at the same rates as the website.

### Security & Privacy

**Q: Is my data secure?**  
A: Yes. We use HTTPS encryption, password hashing, and secure API keys. See our [Privacy Policy](./PRIVACY_POLICY.md).

**Q: Can I change my email?**  
A: No, email cannot be changed for security reasons. Contact support if absolutely necessary.

**Q: How do I change my password?**  
A: Go to Settings → Password & Security → Change Password.

**Q: What is two-factor authentication?**  
A: Extra security layer (coming soon). Requires code from phone in addition to password.

---

## 🎥 Video Tutorials (Coming Soon)

- How to Fund Your Wallet
- Purchasing SMS Numbers
- Using the API
- Managing API Keys
- Electricity Bill Payments

---

## 📧 Contact Support

### Email Support
- **General**: support@fadsms.com
- **Privacy**: privacy@fadsms.com
- **API**: api-support@fadsms.com

### In-App Support
1. Go to Settings → Support & Tickets
2. Click "New Ticket"
3. Fill in subject and message
4. Submit ticket

**Response Time**: Within 24 hours

---

## 🐛 Report a Bug

Found a bug? Help us improve!

**How to Report**:
1. Go to Settings → Support & Tickets
2. Subject: "Bug Report: [Brief description]"
3. Include: 
   - What you were doing
   - What happened
   - What you expected
   - Screenshot if possible

---

## 💡 Feature Requests

Want a new feature? Let us know!

**Submit via**:
- Settings → Support & Tickets
- Email: support@fadsms.com
- Subject: "Feature Request: [Your idea]"

---

## 🔧 Troubleshooting

### Common Issues

**Can't log in**:
- Check email and password
- Clear browser cache
- Try password reset

**Balance not updating**:
- Wait 10-15 seconds after payment
- Check transaction status
- Refresh the page

**SMS code not arriving**:
- Wait up to 5 minutes
- Check different provider
- Contact support with order ID

**Electricity token missing**:
- Check your inbox (may take 5-10 minutes)
- Look for "Processing" status
- Token auto-delivered when ready

**API key not working**:
- Check key is active
- Verify permissions
- Check rate limits
- Ensure correct header: `X-API-Key`

---

## 📱 System Status

Check service status:
- All Systems: Operational ✅
- SMS Providers: 5/5 Working ✅
- VTU Services: Operational ✅
- Payment Processing: Operational ✅

---

## 📚 Documentation Index

### User Guides
- [Terms of Use](./TERMS_OF_USE.md)
- [Privacy Policy](./PRIVACY_POLICY.md)
- [Help Resources](./HELP_RESOURCES.md)

### Technical Documentation
- [API Documentation](./API_DOCUMENTATION.md)
- [API Quick Start](./API_QUICK_START.md)
- [SMS Polling Guide](./SMS_POLLING_GUIDE.md)
- [Timeout Handling](./TIMEOUT_HANDLING.md)
- [Receipt Format](./RECEIPT_FORMAT_UPDATE.md)

### System Documentation
- [Deployment Summary](./DEPLOYMENT_SUMMARY_20251007.md)
- [Settings Features](./SETTINGS_FEATURES_COMPLETE.md)
- [Provider Test Results](./SMS_PROVIDER_TEST_RESULTS.md)

---

## 🎓 Best Practices

### For SMS Verification
1. Choose reliable provider (Tiger SMS for global, Dassy for USA)
2. Keep the page open while waiting for code
3. Use the number immediately (they expire)
4. Check inbox if you miss the code

### For VTU Purchases
1. Verify meter number before purchase
2. Double-check amount
3. Save receipts from inbox
4. For "Processing" status, check inbox in 10 minutes

### For API Integration
1. Keep API keys secure (environment variables)
2. Use IP whitelisting for production
3. Implement retry logic for network errors
4. Monitor rate limits
5. Cache responses when appropriate

---

## 🌟 Tips & Tricks

**Pro Tips**:
- Use dark mode to save battery
- Enable notifications for transaction alerts
- Create separate API keys for dev/prod
- Save frequently used meter numbers
- Check inbox for all receipts and tokens
- Use referral code to earn bonuses

---

## 📞 Emergency Contact

**Critical Issues (Service Down, Payment Issues)**:
- Email: support@fadsms.com
- Mark as: URGENT in subject line

**Response Time**: 
- Normal tickets: 24 hours
- Urgent issues: 4 hours
- Payment issues: 2 hours

---

**Need help? We're here for you!** 💬

Email: support@fadsms.com or submit a ticket in Settings → Support & Tickets
EOF
cat /var/www/api.fadsms.com/HELP_RESOURCES.md | head -80
