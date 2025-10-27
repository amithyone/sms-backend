# 💰 Reseller Wallet & Payment System

## Overview
Complete implementation of the reseller wallet system where resellers pre-fund their panel wallet and customer purchases deduct from the reseller's balance (not the customer's balance).

## 🎯 How It Works

### Revenue Model:

```
Example SMS Service:
├── Platform Base Price: ₦2,000
├── Reseller Gets: 5% discount
├── Reseller Pays: ₦1,900 (95% of base price)
├── Reseller Markup: 10% (configurable 5-10%)
├── Customer Pays: ₦2,200 (base price + 10%)
└── Reseller Profit: ₦300 (₦2,200 - ₦1,900)
```

### Flow:

1. **Reseller funds their panel wallet**
   - Uses PayVibe virtual account
   - Transfers to dedicated account
   - Panel wallet credited automatically

2. **Customer makes purchase**
   - Customer sees marked-up price (base + reseller margin)
   - Customer confirms purchase
   - System deducts from RESELLER's panel wallet (discounted price)
   - Customer gets the service
   - Reseller keeps the margin profit

3. **Profit calculation**:
   ```
   Customer Charge = Platform Price × (1 + Reseller Margin%)
   Reseller Cost = Platform Price × 0.95 (5% discount)
   Reseller Profit = Customer Charge - Reseller Cost
   Platform Revenue = Reseller Cost
   ```

## 💳 Payment Integration

### For Reseller Wallet Top-Ups:
- **Gateway:** PayVibe (main platform's gateway)
- **Process:** Virtual account generation
- **Credit To:** Reseller panel wallet

### For Customer Deposits (Optional):
Resellers can configure their own payment gateways:
- **Paystack** - Popular in Nigeria
- **Flutterwave** - Multi-currency support
- **Process:** Direct to reseller's account
- **Platform:** Not involved in customer payments

## 📊 Reseller Admin Dashboard

### Tab 1: Overview
- Panel information
- Links to subdomain/custom domain

### Tab 2: Fund Wallet ⭐ NEW
- Current panel wallet balance (prominent display)
- Quick amount buttons (₦5k, ₦10k, ₦20k, ₦50k, ₦100k, ₦200k)
- Custom amount input
- Generate virtual account
- Real-time payment tracking
- Auto-refresh on payment completion

**Features:**
- ✅ Minimum ₦1,000 top-up
- ✅ Instant virtual account generation
- ✅ Auto-polling for payment confirmation
- ✅ Copy account details with one click
- ✅ Panel wallet balance updates automatically

### Tab 3: Branding
- Logo, colors, footer customization

### Tab 4: Pricing
- Set SMS, VTU, Data, Electricity margins

### Tab 5: Payment Gateway
- Configure Paystack or Flutterwave
- For customer deposits only

### Tab 6: DNS Setup
- Subdomain info
- Add custom domain
- DNS instructions

## 🔒 Balance Protection

### When Customer Makes Purchase:

1. **Check reseller panel wallet**
   ```php
   if (!$resellerPanel->canAfford($resellerCost)) {
       return error: "Panel wallet insufficient balance"
   }
   ```

2. **Show detailed error to customer**
   ```
   Panel balance: ₦1,500
   Required: ₦1,900
   Please contact panel administrator
   ```

3. **Prevent order if insufficient**
   - Order creation blocked
   - Customer sees clear error message
   - No partial deductions

## 💰 Pricing Example

### Example: WhatsApp Verification

**Platform Settings:**
- Base Price: ₦2,000
- Reseller Discount: 5%

**Reseller A Settings:**
- SMS Margin: 10%

**Reseller B Settings:**
- SMS Margin: 15%

**Results:**

| Party | Reseller A | Reseller B |
|-------|-----------|-----------|
| Customer Pays | ₦2,200 | ₦2,300 |
| Reseller Pays Platform | ₦1,900 | ₦1,900 |
| Reseller Profit | ₦300 | ₦400 |
| Platform Revenue | ₦1,900 | ₦1,900 |

## 📱 Statistics Tracking

### Panel Stats (Reseller Admin Dashboard):
- **Panel Wallet** - Current balance (highlighted)
- **Total Users** - Customer count
- **Total Revenue** - Sum of customer charges
- **Transactions** - Number of purchases
- **Subscription** - Type and expiry

### Main Admin Stats:
- All panel wallets combined
- Total reseller revenue
- Platform revenue from resellers

## 🔧 Technical Implementation

### Database:
✅ `reseller_panels.wallet_balance` - Panel wallet amount
✅ `deposits.metadata` - Tracks reseller top-ups
✅ `sms_orders.metadata` - Tracks pricing breakdown

### Backend:
✅ `ResellerPanel::updateWalletBalance()` - Add/subtract balance
✅ `ResellerPanel::canAfford()` - Check affordability
✅ `SmsController::createOrder()` - Reseller pricing logic
✅ `WalletController` - Reseller wallet top-ups

### Frontend:
✅ `ResellerAdmin` - Wallet tab with top-up
✅ `TopUpModal` - Detects panel and shows gateway
✅ `BrandingContext` - Provides panel_id
✅ Stats display - Wallet balance prominent

## 📋 Reseller Benefits (Updated)

✅ **5% discount on all services** - Buy cheaper than retail
✅ **Set your own markup** - 5-10% configurable
✅ **Keep 100% of profits** - Full margin is yours
✅ **Own payment gateway** - Direct customer payments
✅ **Auto SSL & subdomain** - Technical setup handled
✅ **Custom branding** - White-label experience
✅ **No revenue sharing** - Platform subscription only

## 🚀 Usage Flow

### Initial Setup (Reseller):
1. Apply for panel (₦30k monthly or ₦300k annual)
2. Get approved (subdomain auto-created)
3. Fund panel wallet (₦10k - ₦200k recommended)
4. Configure branding & pricing
5. Share subdomain with customers

### Daily Operations (Reseller):
1. Monitor panel wallet balance
2. Fund wallet when low
3. Track customer purchases
4. View revenue statistics
5. Adjust pricing margins

### Customer Experience:
1. Visit reseller's subdomain
2. See custom branding
3. Register/login
4. Buy services (marked-up prices)
5. Get instant delivery
6. Reseller earns margin profit

## ⚠️ Important Notes

1. **Customers don't fund wallets** - They don't have balances
2. **All purchases** deduct from reseller panel wallet
3. **Reseller must maintain** sufficient wallet balance
4. **Platform gives 5% discount** to all resellers
5. **Reseller keeps margin** between their cost and customer price

## 💡 Profit Calculations

### Monthly Revenue Potential:

```
Assumptions:
- 50 customers
- Average 5 SMS per customer per month = 250 SMS
- Average SMS cost: ₦2,000
- Reseller margin: 10%

Monthly Calculations:
├── Customer Revenue: 250 × ₦2,200 = ₦550,000
├── Cost to Platform: 250 × ₦1,900 = ₦475,000
├── Reseller Profit: ₦75,000
└── After Subscription: ₦75,000 - ₦30,000 = ₦45,000 net

Annual Profit: ₦540,000 with monthly subscription
Annual Profit: ₦600,000 with annual subscription
```

## 🎯 Status

✅ All features implemented
✅ Wallet balance tracking
✅ Purchase deduction logic
✅ Insufficient balance protection
✅ Top-up via PayVibe
✅ Auto-credit on payment
✅ Statistics & reporting
✅ Benefits highlighted

**Status: PRODUCTION READY! 🚀**

---
*Reseller Wallet System - Completed October 12, 2025*

