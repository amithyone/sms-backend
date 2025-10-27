const express = require('express');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

const app = express();
const PORT = 8000;
const JWT_SECRET = 'your-secret-key-change-in-production';

// Middleware
app.use(cors());
app.use(express.json());

// In-memory storage (replace with database in production)
const users = [];
const smsServices = [
  {
    id: 1,
    name: 'WhatsApp',
    conversion_rate: '50.00',
    provider: 'auto',
    description: 'WhatsApp verification service',
    status: 'active',
    priority: 1,
    success_rate: 95
  },
  {
    id: 2,
    name: 'Telegram',
    conversion_rate: '45.00',
    provider: 'auto',
    description: 'Telegram verification service',
    status: 'active',
    priority: 2,
    success_rate: 92
  },
  {
    id: 3,
    name: 'Google',
    conversion_rate: '60.00',
    provider: 'auto',
    description: 'Google verification service',
    status: 'active',
    priority: 1,
    success_rate: 98
  }
];

// --- Mock VTU data ---
const vtuAirtimeNetworks = [
  { id: '1', name: 'MTN', code: 'MTN', status: 'active' },
  { id: '2', name: 'Airtel', code: 'AIRTEL', status: 'active' },
  { id: '3', name: 'Glo', code: 'GLO', status: 'active' },
  { id: '4', name: '9mobile', code: '9MOBILE', status: 'active' },
];

const vtuDataNetworks = [
  { id: '1', name: 'MTN', code: 'MTN', status: 'active' },
  { id: '2', name: 'Airtel', code: 'AIRTEL', status: 'active' },
  { id: '3', name: 'Glo', code: 'GLO', status: 'active' },
  { id: '4', name: '9mobile', code: '9MOBILE', status: 'active' },
];

const vtuDataBundles = {
  MTN: [
    { id: '1', name: '1GB', size: '1GB', validity: '30 days', price: 250, network: 'MTN' },
    { id: '2', name: '2GB', size: '2GB', validity: '30 days', price: 450, network: 'MTN' },
    { id: '3', name: '5GB', size: '5GB', validity: '30 days', price: 1000, network: 'MTN' },
  ],
  AIRTEL: [
    { id: '4', name: '1.5GB', size: '1.5GB', validity: '30 days', price: 300, network: 'AIRTEL' },
    { id: '5', name: '3GB', size: '3GB', validity: '30 days', price: 600, network: 'AIRTEL' },
  ],
  GLO: [
    { id: '6', name: '1GB', size: '1GB', validity: '30 days', price: 200, network: 'GLO' },
    { id: '7', name: '2GB', size: '2GB', validity: '30 days', price: 400, network: 'GLO' },
  ],
  '9MOBILE': [
    { id: '8', name: '1GB', size: '1GB', validity: '30 days', price: 180, network: '9MOBILE' },
    { id: '9', name: '2GB', size: '2GB', validity: '30 days', price: 350, network: '9MOBILE' },
  ],
};

// Routes
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', message: 'Backend server is running' });
});

// SMS Service API
app.get('/sms-service-api.php', (req, res) => {
  const { action } = req.query;
  
  if (action === 'getServices') {
    res.json({
      status: 'success',
      data: smsServices
    });
  } else {
    res.status(400).json({
      status: 'error',
      message: 'Invalid action'
    });
  }
});

// Authentication routes
app.post('/api/register', async (req, res) => {
  try {
    const { email, password, firstName, lastName } = req.body;
    
    // Check if user already exists
    if (users.find(user => user.email === email)) {
      return res.status(400).json({
        status: 'error',
        message: 'User already exists'
      });
    }
    
    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);
    
    // Create user
    const user = {
      id: users.length + 1,
      email,
      password: hashedPassword,
      firstName,
      lastName,
      createdAt: new Date()
    };
    
    users.push(user);
    
    // Generate token
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      JWT_SECRET,
      { expiresIn: '24h' }
    );
    
    res.status(201).json({
      status: 'success',
      message: 'User registered successfully',
      data: {
        user: {
          id: user.id,
          email: user.email,
          firstName: user.firstName,
          lastName: user.lastName
        },
        token
      }
    });
  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({
      status: 'error',
      message: 'Internal server error'
    });
  }
});

app.post('/api/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    
    // Find user
    const user = users.find(u => u.email === email);
    if (!user) {
      return res.status(401).json({
        status: 'error',
        message: 'Invalid credentials'
      });
    }
    
    // Check password
    const isValidPassword = await bcrypt.compare(password, user.password);
    if (!isValidPassword) {
      return res.status(401).json({
        status: 'error',
        message: 'Invalid credentials'
      });
    }
    
    // Generate token
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      JWT_SECRET,
      { expiresIn: '24h' }
    );
    
    res.json({
      status: 'success',
      message: 'Login successful',
      data: {
        user: {
          id: user.id,
          email: user.email,
          firstName: user.firstName,
          lastName: user.lastName
        },
        token
      }
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({
      status: 'error',
      message: 'Internal server error'
    });
  }
});

// Protected route example
app.get('/api/profile', authenticateToken, (req, res) => {
  const user = users.find(u => u.id === req.user.userId);
  if (!user) {
    return res.status(404).json({
      status: 'error',
      message: 'User not found'
    });
  }
  
  res.json({
    status: 'success',
    data: {
      id: user.id,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName
    }
  });
});

// Middleware to authenticate JWT token
function authenticateToken(req, res, next) {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  
  if (!token) {
    return res.status(401).json({
      status: 'error',
      message: 'Access token required'
    });
  }
  
  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({
        status: 'error',
        message: 'Invalid or expired token'
      });
    }
    req.user = user;
    next();
  });
}

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Backend server running on http://localhost:${PORT}`);
  console.log(`📱 SMS Services API: http://localhost:${PORT}/sms-service-api.php?action=getServices`);
  console.log(`🔐 Auth API: http://localhost:${PORT}/api/login`);
  console.log(`✅ Health check: http://localhost:${PORT}/api/health`);
});

// ------------------- VTU API (mock) -------------------
// GET airtime networks
app.get('/api/vtu/airtime/networks', authenticateTokenOptional, (req, res) => {
  res.json({ success: true, data: vtuAirtimeNetworks });
});

// GET data networks
app.get('/api/vtu/data/networks', authenticateTokenOptional, (req, res) => {
  res.json({ success: true, data: vtuDataNetworks });
});

// GET data bundles for a network
app.get('/api/vtu/data/bundles', authenticateTokenOptional, (req, res) => {
  const network = String(req.query.network || '').toUpperCase();
  const bundles = vtuDataBundles[network] || [];
  res.json({ success: true, data: bundles });
});

// POST purchase airtime
app.post('/api/vtu/airtime/purchase', authenticateTokenOptional, (req, res) => {
  const { network, phone, amount } = req.body || {};
  if (!network || !phone) {
    return res.status(400).json({ success: false, message: 'network and phone are required' });
  }
  const reference = `AIR${Date.now()}`;
  res.json({
    success: true,
    data: {
      reference,
      network,
      phone,
      amount: Number(amount || 0),
      status: 'pending',
      message: 'Mock airtime purchase created',
    },
  });
});

// POST purchase data
app.post('/api/vtu/data/purchase', authenticateTokenOptional, (req, res) => {
  const body = req.body || {};
  // Support both frontend schemas:
  // 1) { network, phone, amount, plan, plan_name }
  // 2) { service_id, phone, variation_id, amount }
  const resolvedNetwork = String((body.network || body.service_id || '')).toUpperCase();
  const phone = body.phone;
  const amount = Number(body.amount || 0);
  const plan = body.plan || body.variation_id || null;
  const plan_name = body.plan_name || null;

  if (!resolvedNetwork || !phone) {
    return res.status(400).json({ success: false, message: 'network/service_id and phone are required' });
  }

  const reference = `DATA${Date.now()}`;
  res.json({
    success: true,
    data: {
      reference,
      network: resolvedNetwork,
      phone,
      amount,
      status: 'pending',
      message: 'Mock data purchase created',
      plan,
      plan_name,
    },
  });
});

// GET transaction status
app.get('/api/vtu/transaction/status', authenticateTokenOptional, (req, res) => {
  const reference = String(req.query.reference || '');
  res.json({
    success: true,
    data: {
      reference,
      status: 'pending',
      message: 'Mock transaction pending',
    },
  });
});

// GET provider balance
app.get('/api/vtu/provider/balance', authenticateTokenOptional, (req, res) => {
  res.json({ success: true, data: { balance: 50000, currency: 'NGN' } });
});

// POST validate phone number
app.post('/api/vtu/validate/phone', authenticateTokenOptional, (req, res) => {
  const { phone, network } = req.body || {};
  if (!phone || !network) {
    return res.status(400).json({ success: false, message: 'phone and network are required' });
  }
  res.json({ success: true, data: { is_valid: true, phone, network } });
});

// ------------------- Electricity API -------------------
// GET electricity providers
app.get('/api/electricity/providers', authenticateTokenOptional, (req, res) => {
  const providers = [
    { id: 'ikeja', name: 'Ikeja Electric' },
    { id: 'ekedc', name: 'Eko Electricity Distribution Company' },
    { id: 'aedc', name: 'Abuja Electricity Distribution Company' },
    { id: 'phed', name: 'Port Harcourt Electricity Distribution Company' },
    { id: 'kaedc', name: 'Kano Electricity Distribution Company' },
    { id: 'kedco', name: 'Kaduna Electric Distribution Company' },
    { id: 'jedc', name: 'Jos Electricity Distribution Company' },
    { id: 'eedc', name: 'Enugu Electricity Distribution Company' },
    { id: 'phedc', name: 'Portharcourt Electricity Distribution Company' },
    { id: 'bedc', name: 'Benin Electricity Distribution Company' }
  ];
  res.json({ success: true, data: providers });
});

// POST verify electricity customer
app.post('/api/electricity/verify', authenticateTokenOptional, (req, res) => {
  const { service_id, customer_id, variation_id } = req.body || {};
  
  if (!service_id || !customer_id) {
    return res.status(400).json({ 
      success: false, 
      message: 'service_id and customer_id are required' 
    });
  }

  // Mock verification - in real implementation, call VTU provider
  const mockCustomerNames = {
    '1234567890': 'John Doe',
    '0987654321': 'Jane Smith',
    '1122334455': 'Mike Johnson',
    '5544332211': 'Sarah Wilson'
  };

  const customerName = mockCustomerNames[customer_id] || 'Verified Customer';
  
  res.json({
    success: true,
    data: {
      customer_name: customerName,
      customer_id: customer_id,
      service_id: service_id,
      variation_id: variation_id
    }
  });
});

// POST purchase electricity
app.post('/api/electricity/purchase', authenticateTokenOptional, (req, res) => {
  const { service_id, customer_id, variation_id, amount } = req.body || {};
  
  if (!service_id || !customer_id || !amount) {
    return res.status(400).json({ 
      success: false, 
      message: 'service_id, customer_id, and amount are required' 
    });
  }

  // Simulate processing delay
  setTimeout(() => {
    // Mock success rate (90% success)
    const isSuccess = Math.random() > 0.1;
    
    if (isSuccess) {
      // Generate mock token and units
      const token = Math.random().toString().substr(2, 16).replace(/(.{4})/g, '$1-').slice(0, -1);
      const units = (amount / 50).toFixed(1); // Mock calculation: N50 per unit
      
      const reference = `ELEC${Date.now()}`;
      
      res.json({
        success: true,
        data: {
          reference,
          customer_id,
          service_id,
          variation_id,
          amount: Number(amount),
          token,
          units: `${units} kWh`,
          status: 'completed',
          message: 'Electricity purchase successful',
          receipt: {
            token,
            units: `${units} kWh`,
            amount: Number(amount),
            customer_name: 'Verified Customer',
            date: new Date().toISOString()
          }
        }
      });
    } else {
      res.status(400).json({
        success: false,
        message: 'Electricity purchase failed. Please try again.',
        data: {
          reference: `ELEC${Date.now()}`,
          status: 'failed',
          amount: Number(amount)
        }
      });
    }
  }, 2000); // 2 second delay to simulate processing
});

// ------------------- Transaction Management -------------------
// In-memory transaction storage (replace with database in production)
const transactions = [];
const inboxMessages = [];

// POST create transaction
app.post('/api/transactions', authenticateTokenOptional, (req, res) => {
  const { type, amount, description, reference, status, metadata } = req.body || {};
  
  const transaction = {
    id: transactions.length + 1,
    user_id: req.user?.userId || 1,
    type: type || 'debit',
    amount: Number(amount) || 0,
    description: description || 'Transaction',
    reference: reference || `TXN${Date.now()}`,
    status: status || 'processing',
    metadata: metadata || {},
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };
  
  transactions.push(transaction);
  
  res.json({
    success: true,
    data: transaction
  });
});

// PUT update transaction status
app.put('/api/transactions/:id', authenticateTokenOptional, (req, res) => {
  const { id } = req.params;
  const { status, metadata } = req.body || {};
  
  const transaction = transactions.find(t => t.id === parseInt(id));
  if (!transaction) {
    return res.status(404).json({
      success: false,
      message: 'Transaction not found'
    });
  }
  
  transaction.status = status || transaction.status;
  transaction.metadata = { ...transaction.metadata, ...metadata };
  transaction.updated_at = new Date().toISOString();
  
  res.json({
    success: true,
    data: transaction
  });
});

// POST create inbox message
app.post('/api/inbox/messages', authenticateTokenOptional, (req, res) => {
  const { type, title, message, reference, metadata } = req.body || {};
  
  const inboxMessage = {
    id: inboxMessages.length + 1,
    user_id: req.user?.userId || 1,
    type: type || 'general',
    title: title || 'Notification',
    message: message || '',
    reference: reference || '',
    is_read: false,
    metadata: metadata || {},
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };
  
  inboxMessages.push(inboxMessage);
  
  res.json({
    success: true,
    data: inboxMessage
  });
});

// GET user transactions
app.get('/api/transactions', authenticateTokenOptional, (req, res) => {
  const userId = req.user?.userId || 1;
  const userTransactions = transactions.filter(t => t.user_id === userId);
  
  res.json({
    success: true,
    data: userTransactions.reverse() // Most recent first
  });
});

// GET inbox messages
app.get('/api/inbox/messages', authenticateTokenOptional, (req, res) => {
  const userId = req.user?.userId || 1;
  const { type, is_read, limit = 20, page = 1 } = req.query;
  
  let userMessages = inboxMessages.filter(m => m.user_id === userId);
  
  if (type) {
    userMessages = userMessages.filter(m => m.type === type);
  }
  
  if (is_read !== undefined) {
    userMessages = userMessages.filter(m => m.is_read === (is_read === 'true'));
  }
  
  const startIndex = (page - 1) * limit;
  const endIndex = startIndex + parseInt(limit);
  const paginatedMessages = userMessages.slice(startIndex, endIndex);
  
  res.json({
    success: true,
    data: {
      messages: paginatedMessages,
      total: userMessages.length,
      current_page: parseInt(page),
      last_page: Math.ceil(userMessages.length / limit),
      per_page: parseInt(limit)
    }
  });
});

// GET inbox unread count
app.get('/api/inbox/unread-count', authenticateTokenOptional, (req, res) => {
  const userId = req.user?.userId || 1;
  const unreadCount = inboxMessages.filter(m => m.user_id === userId && !m.is_read).length;
  
  res.json({
    success: true,
    data: {
      unread_count: unreadCount
    }
  });
});

// Allow missing/invalid token for dev convenience
function authenticateTokenOptional(req, res, next) {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];
  if (!token) return next();
  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) return next();
    req.user = user;
    next();
  });
}
