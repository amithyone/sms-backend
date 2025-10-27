# Support Ticket System - Complete Guide

## ✅ Overview

A complete support ticket system has been added to the backend API, allowing users to create tickets and admins to manage them with messaging functionality.

---

## 🗄️ Database Tables

### `support_tickets` Table:
```
id              - Ticket ID
user_id         - User who created the ticket
subject         - Ticket subject
description     - Initial description
status          - open, in_progress, resolved, closed
priority        - low, medium, high, urgent
category        - general, payment, service, technical, other
assigned_to     - Admin user ID (nullable)
resolved_at     - Timestamp when resolved
created_at      - When ticket was created
updated_at      - Last update
```

### `support_messages` Table:
```
id              - Message ID
ticket_id       - Related ticket
user_id         - User who sent message
message         - Message content
attachments     - JSON array of attachments (optional)
is_admin        - Boolean (true if admin reply)
created_at      - When message was sent
updated_at      - Last update
```

---

## 🔗 API Endpoints

### User Endpoints (Authenticated)

#### 1. Get All Tickets (User's own or all for admins)
```
GET /api/support/tickets?status=open&priority=high&category=payment
```

**Query Parameters:**
- `status` - Filter by status (open, in_progress, resolved, closed)
- `priority` - Filter by priority (low, medium, high, urgent)
- `category` - Filter by category (general, payment, service, technical, other)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 4274,
        "subject": "Payment not credited",
        "description": "I made a deposit but balance not updated",
        "status": "open",
        "priority": "high",
        "category": "payment",
        "assigned_to": null,
        "created_at": "2025-10-10T12:00:00.000000Z",
        "messages": [
          {
            "id": 1,
            "message": "I made a deposit but balance not updated",
            "is_admin": false,
            "created_at": "2025-10-10T12:00:00.000000Z"
          }
        ]
      }
    ],
    "total": 5
  }
}
```

---

#### 2. Create New Ticket
```
POST /api/support/tickets
```

**Request Body:**
```json
{
  "subject": "Payment not credited",
  "description": "I made a deposit of ₦1000 but my balance was not updated",
  "category": "payment",
  "priority": "high"
}
```

**Fields:**
- `subject` (required) - Ticket subject (max 255 chars)
- `description` (required) - Detailed description (max 5000 chars)
- `category` (optional) - general, payment, service, technical, other
- `priority` (optional) - low, medium, high, urgent

**Response:**
```json
{
  "status": "success",
  "message": "Support ticket created successfully",
  "data": {
    "ticket": {
      "id": 1,
      "user_id": 4274,
      "subject": "Payment not credited",
      "status": "open",
      "priority": "high",
      "category": "payment",
      "created_at": "2025-10-10T12:00:00.000000Z"
    }
  }
}
```

---

#### 3. Get Ticket Details with All Messages
```
GET /api/support/tickets/{id}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "ticket": {
      "id": 1,
      "subject": "Payment not credited",
      "status": "in_progress",
      "user": {
        "id": 4274,
        "name": "John",
        "email": "user@example.com"
      },
      "messages": [
        {
          "id": 1,
          "message": "I made a deposit but balance not updated",
          "is_admin": false,
          "user": {
            "id": 4274,
            "name": "John"
          },
          "created_at": "2025-10-10T12:00:00Z"
        },
        {
          "id": 2,
          "message": "We're looking into this. Please provide your payment reference.",
          "is_admin": true,
          "user": {
            "id": 86,
            "name": "Admin"
          },
          "created_at": "2025-10-10T12:30:00Z"
        }
      ],
      "assigned_admin": {
        "id": 86,
        "name": "Admin",
        "email": "admin@admin.com"
      }
    }
  }
}
```

---

#### 4. Add Message to Ticket
```
POST /api/support/tickets/{id}/messages
```

**Request Body:**
```json
{
  "message": "My payment reference is PAYVIBE_1759755831_9953"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Message added successfully",
  "data": {
    "message": {
      "id": 3,
      "ticket_id": 1,
      "user_id": 4274,
      "message": "My payment reference is PAYVIBE_1759755831_9953",
      "is_admin": false,
      "created_at": "2025-10-10T13:00:00Z"
    },
    "ticket": {
      "id": 1,
      "status": "open"
    }
  }
}
```

---

### Admin-Only Endpoints

#### 5. Update Ticket Status (Admin Only)
```
PUT /api/support/tickets/{id}/status
```

**Request Body:**
```json
{
  "status": "resolved"
}
```

**Status Options:**
- `open` - Ticket is open and waiting for response
- `in_progress` - Admin is working on it
- `resolved` - Issue has been resolved
- `closed` - Ticket is closed

**Response:**
```json
{
  "status": "success",
  "message": "Ticket status updated successfully",
  "data": {
    "ticket": {
      "id": 1,
      "status": "resolved",
      "resolved_at": "2025-10-10T14:00:00Z"
    }
  }
}
```

---

#### 6. Assign Ticket to Admin (Admin Only)
```
PUT /api/support/tickets/{id}/assign
```

**Request Body:**
```json
{
  "admin_id": 86
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ticket assigned successfully",
  "data": {
    "ticket": {
      "id": 1,
      "assigned_to": 86,
      "assigned_admin": {
        "id": 86,
        "name": "Admin",
        "email": "admin@admin.com"
      }
    }
  }
}
```

---

#### 7. Get Support Statistics (Admin Only)
```
GET /api/support/statistics
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "total": 25,
    "open": 10,
    "in_progress": 8,
    "resolved": 5,
    "closed": 2,
    "by_priority": {
      "low": 5,
      "medium": 12,
      "high": 6,
      "urgent": 2
    },
    "by_category": {
      "general": 8,
      "payment": 10,
      "service": 4,
      "technical": 2,
      "other": 1
    }
  }
}
```

---

## 🔄 Ticket Workflow

### For Users:
1. **Create Ticket** → Status: `open`
2. **Wait for Admin Response** → Status changes to `in_progress`
3. **Reply to Messages** → Status stays or reopens if was resolved
4. **Ticket Resolved** → Status: `resolved`
5. **Ticket Closed** → Status: `closed`

### For Admins:
1. **View All Tickets** → Filter by status, priority, category
2. **Assign to Admin** → Set `assigned_to`
3. **Reply to Ticket** → Status automatically changes to `in_progress`
4. **Mark as Resolved** → Status: `resolved`, sets `resolved_at`
5. **Close Ticket** → Status: `closed`

---

## 💬 Messaging Features

### Auto Status Changes:
- **User creates ticket** → Status: `open`
- **Admin replies** → Status: `open` → `in_progress`
- **User replies to resolved ticket** → Status: `resolved` → `open`
- **Ticket marked resolved** → Sets `resolved_at` timestamp
- **Ticket reopened** → Clears `resolved_at`

### Message Tracking:
- Each message has `is_admin` flag
- Messages linked to user who sent them
- Messages ordered by created_at
- Can attach files (JSON array of attachments)

---

## 🎨 Frontend Integration Examples

### Create Ticket Form:
```javascript
async function createSupportTicket(subject, description, category, priority) {
  const response = await fetch('/api/support/tickets', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      subject,
      description,
      category: category || 'general',
      priority: priority || 'medium'
    })
  });
  
  const result = await response.json();
  return result;
}
```

### View Tickets List:
```javascript
async function getMyTickets(status = '') {
  let url = '/api/support/tickets';
  if (status) url += `?status=${status}`;
  
  const response = await fetch(url, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const result = await response.json();
  return result.data;
}
```

### Send Message:
```javascript
async function replyToTicket(ticketId, message) {
  const response = await fetch(`/api/support/tickets/${ticketId}/messages`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ message })
  });
  
  const result = await response.json();
  return result;
}
```

### Admin: Update Ticket Status:
```javascript
async function updateTicketStatus(ticketId, status) {
  const response = await fetch(`/api/support/tickets/${ticketId}/status`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${adminToken}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ status })
  });
  
  const result = await response.json();
  return result;
}
```

---

## 📱 Mobile UI Example

### Ticket List Card:
```html
<div class="ticket-card">
  <div class="ticket-header">
    <span class="priority-badge priority-high">HIGH</span>
    <span class="status-badge status-open">OPEN</span>
  </div>
  <h3 class="ticket-subject">Payment not credited</h3>
  <p class="ticket-preview">I made a deposit but balance not...</p>
  <div class="ticket-meta">
    <span>🕐 2 hours ago</span>
    <span>💬 3 messages</span>
  </div>
</div>
```

### Ticket Detail View:
```html
<div class="ticket-detail">
  <div class="ticket-info">
    <h2>Payment not credited</h2>
    <div class="badges">
      <span class="priority-high">HIGH</span>
      <span class="status-in-progress">IN PROGRESS</span>
      <span class="category-payment">PAYMENT</span>
    </div>
  </div>
  
  <div class="messages">
    <!-- User message -->
    <div class="message user-message">
      <div class="message-header">
        <strong>You</strong>
        <span>2 hours ago</span>
      </div>
      <div class="message-body">
        I made a deposit but balance not updated
      </div>
    </div>
    
    <!-- Admin message -->
    <div class="message admin-message">
      <div class="message-header">
        <strong>Support Team</strong>
        <span>1 hour ago</span>
      </div>
      <div class="message-body">
        We're looking into this. Please provide your payment reference.
      </div>
    </div>
  </div>
  
  <div class="reply-form">
    <textarea placeholder="Type your reply..."></textarea>
    <button>Send Reply</button>
  </div>
</div>
```

---

## 🎯 Use Cases

### Use Case 1: User Creates Payment Issue Ticket
```bash
POST /api/support/tickets
{
  "subject": "Deposit not credited",
  "description": "Made deposit of ₦1000 with ref PAYVIBE_123 but balance not updated",
  "category": "payment",
  "priority": "high"
}
```

### Use Case 2: Admin Responds
```bash
POST /api/support/tickets/1/messages
{
  "message": "We've found your deposit and will credit your account shortly."
}
```

### Use Case 3: Mark as Resolved
```bash
PUT /api/support/tickets/1/status
{
  "status": "resolved"
}
```

---

## 🛡️ Security Features

- ✅ Users can only view their own tickets
- ✅ Admins can view all tickets
- ✅ Ticket assignment (admin only)
- ✅ Status updates (admin only)
- ✅ All actions authenticated via Sanctum
- ✅ Foreign key constraints for data integrity

---

## 📊 Status Flow

```
[OPEN] 
  ↓ (Admin replies)
[IN PROGRESS]
  ↓ (Admin marks resolved)
[RESOLVED]
  ↓ (User replies)
[OPEN] (reopened)
  ↓ (Or admin closes)
[CLOSED]
```

---

## ✅ Features

### For Users:
- ✅ Create support tickets
- ✅ View their own tickets
- ✅ Add messages/replies
- ✅ Filter by status/priority/category
- ✅ Automatic ticket reopening when replying to resolved tickets

### For Admins:
- ✅ View all support tickets
- ✅ Reply to tickets (auto-changes status to in_progress)
- ✅ Assign tickets to specific admins
- ✅ Update ticket status
- ✅ View statistics (total, by status, by priority, by category)
- ✅ Filter and search tickets

---

## 🧪 Testing

### Test Creating a Ticket:
```bash
curl -X POST "https://api.fadsms.com/api/support/tickets" \
  -H "Authorization: Bearer USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Test ticket",
    "description": "This is a test support ticket",
    "category": "general",
    "priority": "low"
  }'
```

### Test Viewing Tickets:
```bash
curl -X GET "https://api.fadsms.com/api/support/tickets" \
  -H "Authorization: Bearer USER_TOKEN"
```

### Test Admin Reply:
```bash
curl -X POST "https://api.fadsms.com/api/support/tickets/1/messages" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "We are looking into your issue"
  }'
```

---

## 📋 Summary

**Database Tables Created:**
- ✅ `support_tickets` - Ticket records
- ✅ `support_messages` - Ticket messages

**Models Created:**
- ✅ `SupportTicket` - With relationships and scopes
- ✅ `SupportMessage` - With relationships

**Controller Created:**
- ✅ `SupportTicketController` - With all CRUD operations

**Routes Added:**
- ✅ GET /api/support/tickets - List tickets
- ✅ POST /api/support/tickets - Create ticket
- ✅ GET /api/support/tickets/{id} - View ticket
- ✅ POST /api/support/tickets/{id}/messages - Add message
- ✅ PUT /api/support/tickets/{id}/status - Update status (admin)
- ✅ PUT /api/support/tickets/{id}/assign - Assign ticket (admin)
- ✅ GET /api/support/statistics - Get stats (admin)

**Ready for frontend integration!** 🚀

