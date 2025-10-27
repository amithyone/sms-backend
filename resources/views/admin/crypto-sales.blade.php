<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Sales Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 md:relative md:translate-x-0">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-200">
                <div class="text-2xl font-bold text-indigo-600">🔆 Fadded VIP</div>
            </div>
            
            <nav class="p-4 space-y-2">
                <a href="/admin/dashboard" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📊</span><span>Dashboard</span>
                </a>
                <a href="/admin/dashboard#users" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>👥</span><span>Users</span>
                </a>
                <a href="/admin/dashboard#support" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💬</span><span>Support</span>
                </a>
                <a href="/admin/advertisements" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📢</span><span>Advertisements</span>
                </a>
                <a href="/admin/broadcasts" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📣</span><span>Broadcasts</span>
                </a>
                <a href="/admin/crypto-sales" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md bg-slate-100 text-indigo-700 font-semibold">
                    <span>💰</span><span>Crypto Sales</span>
                </a>
                <a href="/admin/reseller-panels" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>🚀</span><span>Child Panels</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">💰 Crypto Sales Management</h1>
                        <p class="text-slate-600">Manage crypto and PayPal sale requests</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600" id="userName">Loading...</span>
                        <button onclick="logout()" class="bg-rose-500 hover:bg-rose-600 text-white text-sm px-3 py-1.5 rounded-md">Logout</button>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Tabs -->
                <div class="mb-6 flex space-x-2 border-b border-gray-200">
                    <button onclick="setTab('requests')" id="tabRequests" class="tab-btn px-4 py-2 font-medium text-sm border-b-2 border-blue-600 text-blue-600">
                        📋 Requests
                    </button>
                    <button onclick="setTab('settings')" id="tabSettings" class="tab-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        ⚙️ Settings
                    </button>
                    <button onclick="setTab('stats')" id="tabStats" class="tab-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        📊 Statistics
                    </button>
                </div>

                <!-- Requests Tab -->
                <div id="requestsTab" class="tab-content">
                    <!-- Status Filter -->
                    <div class="mb-4 flex space-x-2">
                        <button onclick="filterStatus('all')" class="status-filter px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">All</button>
                        <button onclick="filterStatus('pending')" class="status-filter px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm">Pending</button>
                        <button onclick="filterStatus('processing')" class="status-filter px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm">Processing</button>
                        <button onclick="filterStatus('completed')" class="status-filter px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm">Completed</button>
                        <button onclick="filterStatus('rejected')" class="status-filter px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm">Rejected</button>
                    </div>

                    <!-- Sales List -->
                    <div id="salesList" class="space-y-4">
                        <div class="text-center py-8 text-gray-500">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-3"></div>
                            Loading requests...
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="settingsTab" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="settingsContainer">
                        <!-- Settings will be loaded here -->
                    </div>
                </div>

                <!-- Stats Tab -->
                <div id="statsTab" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="statsContainer">
                        <!-- Stats will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- View Request Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
                <h2 class="text-xl font-bold">Request Details</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        const API_BASE = 'https://api.fadsms.com/api/admin';
        let authToken = localStorage.getItem('admin_token');
        let currentFilter = 'all';
        let allSales = [];
        let currentSale = null;

        if (!authToken) window.location.href = '/admin/login';

        document.addEventListener('DOMContentLoaded', () => {
            loadUserInfo();
            loadSales();
            loadSettings();
            loadStats();
        });

        async function loadUserInfo() {
            try {
                const response = await fetch('https://api.fadsms.com/api/admin/dashboard', {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.status === 'success' && data.data.user) {
                    document.getElementById('userName').textContent = data.data.user.name;
                }
            } catch (error) {
                console.error('Failed to load user info:', error);
            }
        }

        function logout() {
            localStorage.removeItem('admin_token');
            window.location.href = '/admin/login';
        }

        // Tab switching
        function setTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-600');
            });
            
            document.getElementById(tab + 'Tab').classList.remove('hidden');
            document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.remove('border-transparent', 'text-gray-600');
        }

        // Filter status
        function filterStatus(status) {
            currentFilter = status;
            document.querySelectorAll('.status-filter').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            event.target.classList.remove('bg-gray-200', 'text-gray-700');
            event.target.classList.add('bg-blue-600', 'text-white');
            
            renderSales();
        }

        // Load sales
        async function loadSales() {
            try {
                const response = await fetch(`${API_BASE}/crypto/sales`, {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    allSales = data.data.data || [];
                    renderSales();
                }
            } catch (error) {
                console.error('Failed to load sales:', error);
                document.getElementById('salesList').innerHTML = 
                    '<div class="text-center py-8 text-red-600">Failed to load requests</div>';
            }
        }

        // Render sales list
        function renderSales() {
            const filtered = currentFilter === 'all' 
                ? allSales 
                : allSales.filter(s => s.status === currentFilter);
            
            const container = document.getElementById('salesList');
            
            if (filtered.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No requests found</div>';
                return;
            }
            
            container.innerHTML = filtered.map(sale => `
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold">${getMethodLabel(sale.payment_method)}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${getStatusClass(sale.status)}">
                                    ${sale.status.toUpperCase()}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-2">
                                <strong>Transaction ID:</strong> ${sale.transaction_id}
                            </div>
                            <div class="text-sm text-gray-600">
                                <strong>User:</strong> ${sale.user?.name || 'Unknown'} (${sale.user?.email || ''})
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900">₦${parseFloat(sale.naira_amount).toLocaleString()}</div>
                            <div class="text-sm text-gray-600">$${sale.crypto_amount} @ ₦${sale.exchange_rate}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <div class="text-gray-600">Phone:</div>
                            <div class="font-medium">${sale.recipient_phone}</div>
                        </div>
                        <div>
                            <div class="text-gray-600">Bank Account:</div>
                            <div class="font-medium">${sale.recipient_account_number}</div>
                            <div class="text-xs text-gray-500">${sale.recipient_account_name} - ${sale.recipient_bank_name}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            ${new Date(sale.created_at).toLocaleString()}
                        </div>
                        <button onclick='viewRequest(${JSON.stringify(sale)})' 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">
                            View Details
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // View request details
        function viewRequest(sale) {
            currentSale = sale;
            const proofImages = Array.isArray(sale.proof_of_payment) ? sale.proof_of_payment : [];
            
            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-6">
                    <!-- User & Transaction Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="font-semibold mb-3">User Information</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong>Name:</strong> ${sale.user?.name || 'N/A'}</div>
                                <div><strong>Email:</strong> ${sale.user?.email || 'N/A'}</div>
                                <div><strong>Phone:</strong> ${sale.user?.phone || sale.recipient_phone}</div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-3">Transaction Details</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong>Method:</strong> ${getMethodLabel(sale.payment_method)}</div>
                                <div><strong>Amount:</strong> $${sale.crypto_amount}</div>
                                <div><strong>Rate:</strong> ₦${sale.exchange_rate}/USD</div>
                                <div class="text-lg font-bold text-green-600">Total: ₦${parseFloat(sale.naira_amount).toLocaleString()}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div>
                        <h3 class="font-semibold mb-3">Payment Details</h3>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                            ${sale.user_wallet_address ? `<div><strong>Wallet Address:</strong> <code class="bg-gray-200 px-2 py-1 rounded">${sale.user_wallet_address}</code></div>` : ''}
                            ${sale.user_paypal_email ? `<div><strong>PayPal Email:</strong> ${sale.user_paypal_email}</div>` : ''}
                        </div>
                    </div>

                    <!-- Bank Account -->
                    <div>
                        <h3 class="font-semibold mb-3">Recipient Bank Account</h3>
                        <div class="bg-blue-50 p-4 rounded-lg space-y-2 text-sm">
                            <div><strong>Account Number:</strong> ${sale.recipient_account_number}</div>
                            <div><strong>Account Name:</strong> ${sale.recipient_account_name}</div>
                            <div><strong>Bank:</strong> ${sale.recipient_bank_name}</div>
                            <div><strong>Phone:</strong> ${sale.recipient_phone}</div>
                        </div>
                    </div>

                    <!-- Proof of Payment -->
                    <div>
                        <h3 class="font-semibold mb-3">Proof of Payment (${proofImages.length} images)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            ${proofImages.map((img, i) => `
                                <a href="${img}" target="_blank" class="block">
                                    <img src="${img}" alt="Proof ${i+1}" class="w-full h-48 object-cover rounded-lg border-2 border-gray-200 hover:border-blue-500 transition-colors">
                                </a>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    ${sale.admin_notes ? `
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                            <div class="font-semibold text-sm mb-1">Admin Notes:</div>
                            <div class="text-sm">${sale.admin_notes}</div>
                        </div>
                    ` : ''}

                    <!-- Actions -->
                    ${sale.status === 'pending' || sale.status === 'processing' ? `
                        <div class="flex space-x-3">
                            <button onclick="updateStatus(${sale.id}, 'processing')" 
                                class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                                ⏳ Mark as Processing
                            </button>
                            <button onclick="approveRequest(${sale.id})" 
                                class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                                ✅ Approve & Credit ₦${parseFloat(sale.naira_amount).toLocaleString()}
                            </button>
                            <button onclick="rejectRequest(${sale.id})" 
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                                ❌ Reject
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('viewModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        // Update status
        async function updateStatus(id, status) {
            const notes = status === 'processing' ? null : prompt('Enter admin notes (optional):');
            
            try {
                const response = await fetch(`${API_BASE}/crypto/sales/${id}/status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status, admin_notes: notes })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Status updated successfully!');
                    closeModal();
                    loadSales();
                    loadStats();
                } else {
                    alert('❌ Failed: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('❌ Error updating status');
            }
        }

        function approveRequest(id) {
            if (!confirm('Confirm you have received the crypto/PayPal payment and want to credit the user?')) return;
            updateStatus(id, 'completed');
        }

        function rejectRequest(id) {
            const reason = prompt('Enter rejection reason:');
            if (!reason) return;
            
            updateStatus(id, 'rejected');
        }

        // Load settings
        async function loadSettings() {
            try {
                const response = await fetch(`${API_BASE}/crypto/settings`, {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderSettings(data.data);
                }
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        }

        function renderSettings(settings) {
            const container = document.getElementById('settingsContainer');
            container.innerHTML = settings.map(setting => `
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-bold mb-4">${getMethodLabel(setting.payment_method)}</h3>
                    
                    <form onsubmit="saveSettings(event, '${setting.payment_method}')" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Rate (₦/USD)</label>
                            <input type="number" step="0.01" id="rate_${setting.payment_method}" value="${setting.rate_per_usd}" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1">Min Amount ($)</label>
                                <input type="number" step="0.01" id="min_${setting.payment_method}" value="${setting.min_amount}" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Max Amount ($)</label>
                                <input type="number" step="0.01" id="max_${setting.payment_method}" value="${setting.max_amount}" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Wallet/Email Address</label>
                            <input type="text" id="address_${setting.payment_method}" 
                                value="${setting.admin_wallet_address || setting.admin_paypal_email || ''}" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Instructions</label>
                            <textarea id="instructions_${setting.payment_method}" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">${setting.instructions || ''}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Disclaimer</label>
                            <textarea id="disclaimer_${setting.payment_method}" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">${setting.disclaimer || ''}</textarea>
                        </div>

                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="enabled_${setting.payment_method}" ${setting.is_enabled ? 'checked' : ''}
                                class="w-5 h-5 accent-green-500">
                            <span class="font-medium">Enable ${setting.payment_method.toUpperCase()} Sales</span>
                        </label>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            💾 Save Settings
                        </button>
                    </form>
                </div>
            `).join('');
        }

        async function saveSettings(event, method) {
            event.preventDefault();
            
            const payload = {
                payment_method: method,
                rate_per_usd: parseFloat(document.getElementById(`rate_${method}`).value),
                min_amount: parseFloat(document.getElementById(`min_${method}`).value),
                max_amount: parseFloat(document.getElementById(`max_${method}`).value),
                is_enabled: document.getElementById(`enabled_${method}`).checked,
                instructions: document.getElementById(`instructions_${method}`).value,
                disclaimer: document.getElementById(`disclaimer_${method}`).value
            };

            if (method === 'paypal') {
                payload.admin_paypal_email = document.getElementById(`address_${method}`).value;
            } else {
                payload.admin_wallet_address = document.getElementById(`address_${method}`).value;
            }

            try {
                const response = await fetch(`${API_BASE}/crypto/settings`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Settings saved successfully!');
                } else {
                    alert('❌ Failed to save settings');
                }
            } catch (error) {
                alert('❌ Error saving settings');
            }
        }

        // Load stats
        async function loadStats() {
            try {
                const response = await fetch(`${API_BASE}/crypto/stats`, {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    renderStats(data.data);
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        function renderStats(stats) {
            document.getElementById('statsContainer').innerHTML = `
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Requests</div>
                    <div class="text-3xl font-bold">${stats.total_requests}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Pending</div>
                    <div class="text-3xl font-bold text-yellow-600">${stats.pending}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Completed</div>
                    <div class="text-3xl font-bold text-green-600">${stats.completed}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Paid</div>
                    <div class="text-2xl font-bold text-blue-600">₦${parseFloat(stats.total_naira_paid || 0).toLocaleString()}</div>
                </div>
            `;
        }

        function getMethodLabel(method) {
            const labels = { 'usdt': '💵 USDT', 'paypal': '💙 PayPal', 'bitcoin': '₿ Bitcoin', 'ethereum': 'Ξ Ethereum' };
            return labels[method] || method;
        }

        function getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-700',
                'processing': 'bg-blue-100 text-blue-700',
                'completed': 'bg-green-100 text-green-700',
                'rejected': 'bg-red-100 text-red-700'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        }
    </script>
</body>
</html>

