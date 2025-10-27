<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Panels - Admin Panel</title>
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
                <a href="/admin/crypto-sales" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💰</span><span>Crypto Sales</span>
                </a>
                <a href="/admin/reseller-panels" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md bg-slate-100 text-indigo-700 font-semibold">
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
                        <h1 class="text-2xl font-bold text-slate-900">🚀 Reseller/Child Panel Management</h1>
                        <p class="text-slate-600">Manage white-label reseller applications and panels</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600" id="userName">Loading...</span>
                        <button onclick="logout()" class="bg-rose-500 hover:bg-rose-600 text-white text-sm px-3 py-1.5 rounded-md">Logout</button>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-600 mb-1">Total Panels</div>
                        <div class="text-3xl font-bold text-gray-900" id="totalPanels">0</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-600 mb-1">Active</div>
                        <div class="text-3xl font-bold text-green-600" id="activePanels">0</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-600 mb-1">Pending</div>
                        <div class="text-3xl font-bold text-yellow-600" id="pendingPanels">0</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="text-sm text-gray-600 mb-1">Monthly Revenue</div>
                        <div class="text-2xl font-bold text-blue-600" id="monthlyRevenue">₦0</div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="mb-4 flex space-x-2 border-b border-gray-200">
                    <button onclick="filterStatus('all')" id="filterAll" class="filter-btn px-4 py-2 font-medium text-sm border-b-2 border-blue-600 text-blue-600">
                        All Panels
                    </button>
                    <button onclick="filterStatus('pending')" id="filterPending" class="filter-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        Pending
                    </button>
                    <button onclick="filterStatus('active')" id="filterActive" class="filter-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        Active
                    </button>
                    <button onclick="filterStatus('suspended')" id="filterSuspended" class="filter-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900">
                        Suspended
                    </button>
                </div>

                <!-- Panels List -->
                <div id="panelsList" class="space-y-4">
                    <div class="text-center py-8 text-gray-500">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-3"></div>
                        Loading panels...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- View/Edit Panel Modal -->
    <div id="panelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
                <h2 class="text-xl font-bold">Panel Details</h2>
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
        let allPanels = [];
        let currentFilter = 'all';

        if (!authToken) window.location.href = '/admin/login';

        document.addEventListener('DOMContentLoaded', () => {
            loadUserInfo();
            loadStats();
            loadPanels();
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

        async function loadStats() {
            try {
                const response = await fetch(`${API_BASE}/reseller/stats`, {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    document.getElementById('totalPanels').textContent = data.data.total_panels;
                    document.getElementById('activePanels').textContent = data.data.active_panels;
                    document.getElementById('pendingPanels').textContent = data.data.pending_panels;
                    document.getElementById('monthlyRevenue').textContent = '₦' + parseFloat(data.data.total_revenue || 0).toLocaleString();
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        async function loadPanels() {
            try {
                const response = await fetch(`${API_BASE}/reseller/panels`, {
                    headers: { 'Authorization': `Bearer ${authToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    allPanels = data.data.data || [];
                    renderPanels();
                }
            } catch (error) {
                console.error('Failed to load panels:', error);
                document.getElementById('panelsList').innerHTML = 
                    '<div class="text-center py-8 text-red-600">Failed to load panels</div>';
            }
        }

        function filterStatus(status) {
            currentFilter = status;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-600');
            });
            document.getElementById('filter' + status.charAt(0).toUpperCase() + status.slice(1)).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('filter' + status.charAt(0).toUpperCase() + status.slice(1)).classList.remove('border-transparent');
            
            renderPanels();
        }

        function renderPanels() {
            const filtered = currentFilter === 'all' 
                ? allPanels 
                : allPanels.filter(p => p.status === currentFilter);
            
            const container = document.getElementById('panelsList');
            
            if (filtered.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No panels found</div>';
                return;
            }
            
            container.innerHTML = filtered.map(panel => `
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold">${panel.panel_name}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${getStatusClass(panel.status)}">
                                    ${panel.status.toUpperCase()}
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div><strong>Owner:</strong> ${panel.owner?.name || 'Unknown'} (${panel.owner?.email || ''})</div>
                                <div><strong>Subdomain:</strong> <a href="https://${panel.subdomain}.fadsms.com" target="_blank" class="text-blue-600 hover:underline">${panel.subdomain}.fadsms.com</a></div>
                                ${panel.custom_domain ? `<div><strong>Custom Domain:</strong> <a href="https://${panel.custom_domain}" target="_blank" class="text-blue-600 hover:underline">${panel.custom_domain}</a></div>` : ''}
                                <div><strong>Brand:</strong> ${panel.brand_name || 'Not set'}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold text-gray-900">₦${parseFloat(panel.subscription_fee).toLocaleString()}</div>
                            <div class="text-sm text-gray-600">${panel.subscription_type}</div>
                            ${panel.subscription_expires_at ? `<div class="text-xs text-gray-500 mt-1">Expires: ${new Date(panel.subscription_expires_at).toLocaleDateString()}</div>` : ''}
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                        <div>
                            <div class="text-gray-600">Total Users</div>
                            <div class="font-bold">${panel.total_users || 0}</div>
                        </div>
                        <div>
                            <div class="text-gray-600">Transactions</div>
                            <div class="font-bold">${panel.total_transactions || 0}</div>
                        </div>
                        <div>
                            <div class="text-gray-600">Revenue</div>
                            <div class="font-bold">₦${parseFloat(panel.total_revenue || 0).toLocaleString()}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            Created: ${new Date(panel.created_at).toLocaleDateString()}
                        </div>
                        <div class="flex space-x-2">
                            <button onclick='viewPanel(${JSON.stringify(panel)})' 
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">
                                View Details
                            </button>
                            ${panel.status === 'pending' ? `
                                <button onclick="approvePanel(${panel.id})" 
                                    class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg">
                                    ✅ Approve
                                </button>
                                <button onclick="rejectPanel(${panel.id})" 
                                    class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg">
                                    ❌ Reject
                                </button>
                            ` : ''}
                            ${panel.status === 'active' ? `
                                <button onclick="suspendPanel(${panel.id})" 
                                    class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-lg">
                                    ⏸️ Suspend
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function viewPanel(panel) {
            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-3">Panel Information</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong>Panel Name:</strong> ${panel.panel_name}</div>
                                <div><strong>Brand Name:</strong> ${panel.brand_name || 'Not set'}</div>
                                <div><strong>Subdomain:</strong> ${panel.subdomain}.fadsms.com</div>
                                ${panel.custom_domain ? `<div><strong>Custom Domain:</strong> ${panel.custom_domain}</div>` : ''}
                                <div><strong>Status:</strong> <span class="px-2 py-1 rounded-full text-xs ${getStatusClass(panel.status)}">${panel.status}</span></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-3">Owner Information</h3>
                            <div class="space-y-2 text-sm">
                                <div><strong>Name:</strong> ${panel.owner?.name || 'N/A'}</div>
                                <div><strong>Email:</strong> ${panel.owner?.email || 'N/A'}</div>
                                <div><strong>Phone:</strong> ${panel.owner?.phone || 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-3">Subscription Details</h3>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                            <div><strong>Type:</strong> ${panel.subscription_type === 'annual' ? 'Annual (₦300,000/year)' : 'Monthly (₦30,000/month)'}</div>
                            <div><strong>Fee:</strong> ₦${parseFloat(panel.subscription_fee).toLocaleString()}</div>
                            ${panel.last_payment_at ? `<div><strong>Last Payment:</strong> ${new Date(panel.last_payment_at).toLocaleDateString()}</div>` : ''}
                            ${panel.subscription_expires_at ? `<div><strong>Expires:</strong> ${new Date(panel.subscription_expires_at).toLocaleDateString()}</div>` : ''}
                            <div><strong>Payment Status:</strong> ${panel.is_paid ? '✅ Paid' : '❌ Unpaid'}</div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-3">Pricing Margins</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-gray-600">SMS Margin</div>
                                <div class="text-lg font-bold text-blue-600">${panel.sms_margin_percentage || 10}%</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="text-gray-600">VTU Margin</div>
                                <div class="text-lg font-bold text-green-600">${panel.vtu_margin_percentage || 5}%</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <div class="text-gray-600">Data Margin</div>
                                <div class="text-lg font-bold text-purple-600">${panel.data_margin_percentage || 5}%</div>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-lg">
                                <div class="text-gray-600">Electricity Margin</div>
                                <div class="text-lg font-bold text-yellow-600">${panel.electricity_margin_percentage || 5}%</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-3">Payment Gateway</h3>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                            <div><strong>Gateway:</strong> ${panel.payment_gateway ? panel.payment_gateway.toUpperCase() : 'Not configured'}</div>
                            <div><strong>Status:</strong> ${panel.payment_gateway_enabled ? '✅ Enabled' : '❌ Disabled'}</div>
                            ${panel.paystack_public_key ? `<div><strong>Paystack Public Key:</strong> ${panel.paystack_public_key.substring(0, 20)}...</div>` : ''}
                            ${panel.payvibe_api_key ? `<div><strong>PayVibe API Key:</strong> ${panel.payvibe_api_key.substring(0, 20)}...</div>` : ''}
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-3">Statistics</h3>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900">${panel.total_users || 0}</div>
                                <div class="text-gray-600">Users</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900">${panel.total_transactions || 0}</div>
                                <div class="text-gray-600">Transactions</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-900">₦${parseFloat(panel.total_revenue || 0).toLocaleString()}</div>
                                <div class="text-gray-600">Revenue</div>
                            </div>
                        </div>
                    </div>

                    ${panel.status === 'pending' ? `
                        <div class="flex space-x-3">
                            <button onclick="approvePanel(${panel.id})" 
                                class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium">
                                ✅ Approve Panel
                            </button>
                            <button onclick="rejectPanel(${panel.id})" 
                                class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                                ❌ Reject Application
                            </button>
                        </div>
                    ` : ''}

                    ${panel.status === 'active' ? `
                        <div class="flex space-x-3">
                            <button onclick="suspendPanel(${panel.id})" 
                                class="flex-1 px-4 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium">
                                ⏸️ Suspend Panel
                            </button>
                            <a href="https://${panel.subdomain}.fadsms.com" target="_blank"
                                class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-center">
                                🔗 Visit Panel
                            </a>
                        </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('panelModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('panelModal').classList.add('hidden');
        }

        async function approvePanel(id) {
            if (!confirm('Approve this reseller panel? The panel will be activated immediately.')) return;
            
            try {
                const response = await fetch(`${API_BASE}/reseller/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Panel approved and activated!');
                    closeModal();
                    loadStats();
                    loadPanels();
                } else {
                    alert('❌ Failed to approve: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('❌ Error approving panel');
            }
        }

        async function rejectPanel(id) {
            const reason = prompt('Enter rejection reason (will be sent to user):');
            if (!reason) return;
            
            try {
                const response = await fetch(`${API_BASE}/reseller/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Application rejected and refunded');
                    closeModal();
                    loadStats();
                    loadPanels();
                } else {
                    alert('❌ Failed to reject: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('❌ Error rejecting application');
            }
        }

        async function suspendPanel(id) {
            if (!confirm('Suspend this panel? Users won\'t be able to access it.')) return;
            
            try {
                // TODO: Add suspend endpoint
                alert('Suspend functionality will be implemented');
            } catch (error) {
                alert('❌ Error suspending panel');
            }
        }

        function getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-700',
                'active': 'bg-green-100 text-green-700',
                'suspended': 'bg-red-100 text-red-700',
                'cancelled': 'bg-gray-100 text-gray-700'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        }
    </script>
</body>
</html>

