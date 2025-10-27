# Admin Dashboard - Deposit & Transaction Status Integration Guide

## 🎯 Overview

This guide shows how to add deposit status editing and transaction status management to the admin dashboard at [https://api.fadsms.com/admin/dashboard](https://api.fadsms.com/admin/dashboard).

**Currently Available:** 9 pending deposits waiting for status update!

---

## 📊 Current Pending Deposits

```
ID  | User Email                    | Amount    | Status  | Created
----|-------------------------------|-----------|---------|----------
13  | jameswrite856@gmail.com       | ₦1,000.00 | pending | Oct 10
12  | jameswrite856@gmail.com       | ₦1,000.00 | pending | Oct 10
11  | jameswrite856@gmail.com       | ₦1,000.00 | pending | Oct 10
8   | imax9ja@gmail.com             | ₦1,000.00 | pending | Oct 7
6   | imax9ja@gmail.com             | ₦1,000.00 | pending | Oct 6
```

**These need admin approval/denial interface!**

---

## 🔗 Backend APIs (Already Built & Ready)

### 1. Get All Deposits
```javascript
GET /api/admin/deposits?status=pending&per_page=20

Headers:
Authorization: Bearer {admin_token}
Accept: application/json
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 13,
        "user_id": 18117,
        "amount": "1000.00",
        "reference": "PAYVIBE_1760084491_6280",
        "status": "pending",
        "created_at": "2025-10-10T08:21:31.000000Z",
        "user": {
          "id": 18117,
          "name": "James",
          "email": "jameswrite856@gmail.com"
        }
      }
    ],
    "total": 9
  }
}
```

---

### 2. Get Pending Deposits Only
```javascript
GET /api/admin/deposits/pending

Headers:
Authorization: Bearer {admin_token}
Accept: application/json
```

---

### 3. Update Deposit Status (APPROVE/DENY)
```javascript
PUT /api/admin/deposits/{id}/status

Headers:
Authorization: Bearer {admin_token}
Content-Type: application/json

Body:
{
  "status": "completed",  // or "failed", "cancelled"
  "admin_note": "Payment verified via bank transfer"
}
```

**Status Options:**
- `completed` - Approve deposit (credits user balance automatically)
- `failed` - Deny deposit (payment failed/rejected)
- `cancelled` - Cancel deposit (admin cancelled)

**Response:**
```json
{
  "status": "success",
  "message": "Deposit status updated successfully",
  "data": {
    "deposit": {
      "id": 13,
      "status": "completed",
      "metadata": {
        "admin_note": "Payment verified",
        "processed_at": "2025-10-10T12:00:00",
        "processed_by": 4274,
        "processed_by_name": "admin"
      }
    }
  }
}
```

---

### 4. Update Transaction Status
```javascript
PUT /api/admin/transactions/{id}/status

Headers:
Authorization: Bearer {admin_token}
Content-Type: application/json

Body:
{
  "status": "success",  // or "pending", "failed", "cancelled"
  "admin_note": "Manual verification completed"
}
```

---

## 🎨 Frontend UI Implementation

### Option 1: Add Deposits Section to Dashboard

**Location:** Add a new "Deposits Management" section

```html
<!-- Deposits Section -->
<div class="deposits-section">
  <h2>Pending Deposits ({{ pendingCount }})</h2>
  
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Amount</th>
        <th>Reference</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="deposit in deposits" :key="deposit.id">
        <td>#{{ deposit.id }}</td>
        <td>
          {{ deposit.user.name }}<br>
          <small>{{ deposit.user.email }}</small>
        </td>
        <td>₦{{ deposit.amount }}</td>
        <td>{{ deposit.reference }}</td>
        <td>{{ formatDate(deposit.created_at) }}</td>
        <td>
          <button @click="openApproveModal(deposit)" class="btn-approve">
            ✅ Approve
          </button>
          <button @click="openDenyModal(deposit)" class="btn-deny">
            ❌ Deny
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

---

### Option 2: Modal for Approve/Deny

```html
<!-- Approve/Deny Modal -->
<div v-if="showModal" class="modal">
  <div class="modal-content">
    <h3>{{ modalTitle }}</h3>
    
    <div class="deposit-details">
      <p><strong>Deposit ID:</strong> #{{ selectedDeposit.id }}</p>
      <p><strong>User:</strong> {{ selectedDeposit.user.name }}</p>
      <p><strong>Email:</strong> {{ selectedDeposit.user.email }}</p>
      <p><strong>Amount:</strong> ₦{{ selectedDeposit.amount }}</p>
      <p><strong>Reference:</strong> {{ selectedDeposit.reference }}</p>
    </div>
    
    <div class="form-group">
      <label>Status:</label>
      <select v-model="newStatus">
        <option value="completed">✅ Approve (Credit User)</option>
        <option value="failed">❌ Deny (Payment Failed)</option>
        <option value="cancelled">⚫ Cancel</option>
      </select>
    </div>
    
    <div class="form-group">
      <label>Admin Note (Optional):</label>
      <textarea v-model="adminNote" rows="3" 
                placeholder="Reason for approval/denial..."></textarea>
    </div>
    
    <div v-if="newStatus === 'completed'" class="warning">
      ⚠️ User balance will be credited with ₦{{ selectedDeposit.amount }}
    </div>
    
    <div class="modal-actions">
      <button @click="closeModal()" class="btn-cancel">Cancel</button>
      <button @click="updateDepositStatus()" class="btn-submit">
        {{ submitButtonText }}
      </button>
    </div>
  </div>
</div>
```

---

### JavaScript/Vue.js Implementation

```javascript
// 1. Fetch Pending Deposits
async function fetchPendingDeposits() {
  try {
    const response = await fetch('https://api.fadsms.com/api/admin/deposits/pending', {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const result = await response.json();
    
    if (result.status === 'success') {
      this.deposits = result.data.data;
      this.pendingCount = result.data.total;
    }
  } catch (error) {
    console.error('Error fetching deposits:', error);
  }
}

// 2. Update Deposit Status
async function updateDepositStatus() {
  try {
    const response = await fetch(
      `https://api.fadsms.com/api/admin/deposits/${this.selectedDeposit.id}/status`,
      {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          status: this.newStatus,
          admin_note: this.adminNote
        })
      }
    );
    
    const result = await response.json();
    
    if (result.status === 'success') {
      // Show success message
      this.showNotification('✅ Deposit status updated successfully!');
      
      // Refresh deposits list
      await this.fetchPendingDeposits();
      
      // Close modal
      this.closeModal();
    } else {
      this.showNotification('❌ Error: ' + result.message, 'error');
    }
  } catch (error) {
    console.error('Error updating deposit:', error);
    this.showNotification('❌ Failed to update deposit status', 'error');
  }
}

// 3. Open Approve Modal
function openApproveModal(deposit) {
  this.selectedDeposit = deposit;
  this.newStatus = 'completed';
  this.adminNote = '';
  this.modalTitle = 'Approve Deposit';
  this.showModal = true;
}

// 4. Open Deny Modal
function openDenyModal(deposit) {
  this.selectedDeposit = deposit;
  this.newStatus = 'failed';
  this.adminNote = '';
  this.modalTitle = 'Deny Deposit';
  this.showModal = true;
}
```

---

### React Implementation

```javascript
import React, { useState, useEffect } from 'react';

function DepositsManagement() {
  const [deposits, setDeposits] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [selectedDeposit, setSelectedDeposit] = useState(null);
  const [newStatus, setNewStatus] = useState('completed');
  const [adminNote, setAdminNote] = useState('');
  
  const adminToken = localStorage.getItem('adminToken');
  
  // Fetch deposits
  useEffect(() => {
    fetchPendingDeposits();
  }, []);
  
  const fetchPendingDeposits = async () => {
    const response = await fetch('https://api.fadsms.com/api/admin/deposits/pending', {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const result = await response.json();
    if (result.status === 'success') {
      setDeposits(result.data.data);
    }
  };
  
  const updateDepositStatus = async () => {
    const response = await fetch(
      `https://api.fadsms.com/api/admin/deposits/${selectedDeposit.id}/status`,
      {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          status: newStatus,
          admin_note: adminNote
        })
      }
    );
    
    const result = await response.json();
    
    if (result.status === 'success') {
      alert('✅ Deposit status updated successfully!');
      fetchPendingDeposits();
      setShowModal(false);
    } else {
      alert('❌ Error: ' + result.message);
    }
  };
  
  return (
    <div className="deposits-management">
      <h2>Pending Deposits ({deposits.length})</h2>
      
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Amount</th>
            <th>Reference</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {deposits.map(deposit => (
            <tr key={deposit.id}>
              <td>#{deposit.id}</td>
              <td>
                {deposit.user.name}<br/>
                <small>{deposit.user.email}</small>
              </td>
              <td>₦{deposit.amount}</td>
              <td>{deposit.reference}</td>
              <td>
                <button onClick={() => {
                  setSelectedDeposit(deposit);
                  setNewStatus('completed');
                  setShowModal(true);
                }}>
                  ✅ Approve
                </button>
                <button onClick={() => {
                  setSelectedDeposit(deposit);
                  setNewStatus('failed');
                  setShowModal(true);
                }}>
                  ❌ Deny
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      
      {/* Modal component would go here */}
    </div>
  );
}
```

---

## 🎨 CSS Styling Suggestions

```css
.deposits-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-approve {
  background: #10b981;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 8px;
}

.btn-approve:hover {
  background: #059669;
}

.btn-deny {
  background: #ef4444;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-deny:hover {
  background: #dc2626;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  padding: 24px;
  max-width: 500px;
  width: 90%;
}

.warning {
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  padding: 12px;
  margin: 16px 0;
  border-radius: 4px;
}
```

---

## 📱 Mobile-Optimized UI

Since the site is mobile view only [[memory:3869258]], here's a mobile-first design:

```html
<div class="deposits-mobile">
  <div v-for="deposit in deposits" :key="deposit.id" class="deposit-card">
    <div class="deposit-header">
      <span class="deposit-id">#{{ deposit.id }}</span>
      <span class="deposit-amount">₦{{ deposit.amount }}</span>
    </div>
    
    <div class="deposit-user">
      <strong>{{ deposit.user.name }}</strong><br>
      <small>{{ deposit.user.email }}</small>
    </div>
    
    <div class="deposit-reference">
      Ref: {{ deposit.reference }}
    </div>
    
    <div class="deposit-date">
      {{ formatDate(deposit.created_at) }}
    </div>
    
    <div class="deposit-actions">
      <button @click="approve(deposit)" class="btn-approve-mobile">
        ✅ Approve
      </button>
      <button @click="deny(deposit)" class="btn-deny-mobile">
        ❌ Deny
      </button>
    </div>
  </div>
</div>
```

---

## 🔔 Notification System

Use customized alerts instead of default browser dialogs [[memory:3574000]]:

```javascript
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.classList.add('show');
  }, 10);
  
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
```

```css
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 16px 24px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  opacity: 0;
  transform: translateY(-20px);
  transition: all 0.3s;
  z-index: 2000;
}

.notification.show {
  opacity: 1;
  transform: translateY(0);
}

.notification-success {
  background: #10b981;
  color: white;
}

.notification-error {
  background: #ef4444;
  color: white;
}
```

---

## 📋 Complete Integration Checklist

### Backend (✅ Already Done)
- [x] GET /api/admin/deposits - List all deposits
- [x] GET /api/admin/deposits/pending - List pending deposits
- [x] PUT /api/admin/deposits/{id}/status - Update deposit status
- [x] PUT /api/admin/transactions/{id}/status - Update transaction status
- [x] Automatic balance crediting on approval
- [x] Admin audit trail in metadata
- [x] Cache cleared and routes registered

### Frontend (Todo)
- [ ] Add deposits section to dashboard
- [ ] Create approve/deny modal
- [ ] Implement API calls
- [ ] Add loading states
- [ ] Add error handling
- [ ] Add success notifications
- [ ] Add mobile-responsive design
- [ ] Test with real deposits

---

## 🧪 Quick Test

Test the APIs directly first:

```bash
# 1. Get pending deposits
curl -X GET "https://api.fadsms.com/api/admin/deposits/pending" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# 2. Approve deposit #13
curl -X PUT "https://api.fadsms.com/api/admin/deposits/13/status" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "admin_note": "Payment verified via bank transfer"
  }'
```

---

## 🎯 Summary

**What You Need to Add to Dashboard:**

1. **Deposits Table/Cards** - Show pending deposits
2. **Action Buttons** - Approve/Deny for each deposit
3. **Modal/Dialog** - For confirming action with admin note
4. **API Integration** - Call the existing backend endpoints
5. **Notifications** - Custom alerts for success/error

**Backend is Ready!** Just add the frontend UI to connect to these endpoints.

**Current Pending Deposits:** 9 deposits totaling ₦9,000.00 waiting for approval!

---

## 📖 Related Documentation

- **Backend API Details:** `/var/www/api.fadsms.com/ADMIN_DEPOSIT_REFUND_GUIDE.md`
- **Transaction Status Management:** `/var/www/api.fadsms.com/ADMIN_TRANSACTION_STATUS_MANAGEMENT.md`

**Everything is ready for frontend integration!** 🚀

