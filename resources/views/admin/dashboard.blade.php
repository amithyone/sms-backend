<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaddedSMS Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Keep existing utility classes used by JS */
        .loading { text-align: center; padding: 2rem; color: #64748b; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin: 1rem; }
        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-suspended { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-slate-50 text-slate-700">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="hidden md:block w-64 bg-white border-r border-slate-200">
            <div class="px-6 py-5 border-b border-slate-200">
                <div class="text-indigo-600 font-bold text-xl">🔆 Fadded VIP</div>
                <div class="text-xs text-slate-500">Admin Panel</div>
            </div>
            <nav class="p-4 space-y-1">
                <button data-tab="overview" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('overview')">Dashboard</button>
                <button data-tab="sms" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('sms')">SMS Orders</button>
                <button data-tab="vtu" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('vtu')">VTU Orders</button>
                <button data-tab="users" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('users')">Users</button>
                <button data-tab="deposits" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('deposits')">Deposits</button>
                <button data-tab="transactions" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('transactions')">Transactions</button>
                <button data-tab="pricing" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('pricing')">Pricing</button>
                <button data-tab="apiservices" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('apiservices')">API Services</button>
            </nav>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-4 flex items-center justify-between">
                <div class="md:hidden">
                    <div class="text-indigo-600 font-bold text-lg">🔆 Fadded VIP</div>
                </div>
                <div class="flex-1 md:px-4">
                    <div class="text-lg font-semibold">Admin Dashboard</div>
                    <div class="text-xs text-slate-500">Overview and management</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm" id="userName">Loading...</span>
                    <button class="bg-rose-500 hover:bg-rose-600 text-white text-sm px-3 py-1.5 rounded-md" onclick="logout()">Logout</button>
                </div>
            </header>

            <!-- Content -->
            <main class="p-4 md:p-6">
                <div id="loading" class="loading">Loading dashboard...</div>

                <div id="dashboard" style="display:none;" class="space-y-6">
                    <!-- Stats Cards -->
                    <div id="statsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

                    <!-- Overview sections -->
                    <section id="recentUsersSection" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Recent Users</h2>
                            <a href="#" class="text-sm text-indigo-600" onclick="showUsers()">View All</a>
                        </div>
                        <div id="recentUsers" class="p-4"></div>
                    </section>

                    <section id="recentTransactionsSection" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Recent Transactions</h2>
                            <a href="#" class="text-sm text-indigo-600" onclick="showTransactions()">View All</a>
                        </div>
                        <div id="recentTransactions" class="p-4"></div>
                    </section>

                    <section id="recentDepositsSection" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Recent Deposits</h2>
                            <a href="#" class="text-sm text-indigo-600" onclick="showDeposits()">View All</a>
                        </div>
                        <div id="recentDeposits" class="p-4"></div>
                    </section>

                    <!-- SMS Orders -->
                    <section id="smsOrdersSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <h2 class="font-semibold">SMS / Virtual Number Orders</h2>
                </div>
                        <div id="smsOrders" class="p-4"></div>
                    </section>

                    <!-- VTU Orders -->
                    <section id="vtuOrdersSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <h2 class="font-semibold">VTU Orders</h2>
            </div>
                        <div id="vtuOrders" class="p-4"></div>
                    </section>

                    <!-- Users Management -->
                    <section id="usersSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Users</h2>
                            <div class="flex items-center gap-2">
                                <input id="usersSearch" type="text" placeholder="Search users..." class="border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="filterUsersLocal(); debouncedUsersLoad()" />
                                <select id="usersRole" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadUsers()">
                                    <option value="">All roles</option>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="super_admin">Super Admin</option>
                                </select>
                                <select id="usersStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadUsers()">
                                    <option value="">All status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        <div id="usersTable" class="p-4"></div>
                    </section>

                    <!-- Deposits Management -->
                    <section id="depositsSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Deposits</h2>
                            <div class="flex items-center gap-2">
                                <input id="depositsSearch" type="text" placeholder="Search reference/user..." class="border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="debouncedDepositsLoad()" />
                                <select id="depositsStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadDeposits()">
                                    <option value="">All status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div id="depositsTable" class="p-4"></div>
                    </section>

                    <!-- Transactions Page -->
                    <section id="transactionsSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">Transactions</h2>
                            <div class="flex items-center gap-2">
                                <input id="txSearch" type="text" placeholder="Search description/user/ref" class="border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="debouncedTransactionsLoad()" />
                                <input id="txFrom" type="date" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()" />
                                <input id="txTo" type="date" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()" />
                                <select id="txType" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()">
                                    <option value="">All types</option>
                                    <option value="credit">Credit</option>
                                    <option value="debit">Debit</option>
                                    <option value="service_purchase">Service Purchase</option>
                                </select>
                                <select id="txStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()">
                                    <option value="">All status</option>
                                    <option value="pending">Pending</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <select id="txSortBy" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()">
                                    <option value="created_at">Date</option>
                                    <option value="amount">Amount</option>
                                    <option value="status">Status</option>
                                    <option value="type">Type</option>
                                </select>
                                <select id="txSortDir" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadTransactions()">
                                    <option value="desc">Desc</option>
                                    <option value="asc">Asc</option>
                                </select>
                                <a id="txExport" href="#" class="text-sm text-indigo-600" onclick="exportTransactions()">Export CSV</a>
                            </div>
                        </div>
                        <div id="transactionsTable" class="p-4"></div>
                    </section>

                    <!-- Pricing -->
                    <section id="pricingSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <h2 class="font-semibold">Pricing Regulation</h2>
                        </div>
                        <div class="p-4">
                            <form id="pricingForm" onsubmit="return savePricing(event)" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <label class="block">
                                        <span class="text-sm text-slate-600">Markup Percentage (%)</span>
                                        <input id="markupPercent" type="number" min="0" max="100" step="0.1" class="mt-1 w-full border border-slate-300 rounded-md px-3 py-2" />
                                    </label>
                                    <label class="block">
                                        <span class="text-sm text-slate-600">Currency</span>
                                        <select id="currency" class="mt-1 w-full border border-slate-300 rounded-md px-3 py-2">
                                            <option value="NGN">NGN</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="text-sm text-slate-600">Auto FX Conversion</span>
                                        <select id="autoFx" class="mt-1 w-full border border-slate-300 rounded-md px-3 py-2">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </label>
                                </div>
                                <div>
                                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md" type="submit">Save</button>
                                    <span id="pricingStatus" class="ml-2 text-sm text-slate-500"></span>
                                </div>
                            </form>
                </div>
                    </section>

                    <!-- API Services Management -->
                    <section id="apiServicesSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                            <h2 class="font-semibold">API Services</h2>
                            <div class="flex items-center gap-2">
                                <input id="svcSearch" type="text" placeholder="Search services..." class="border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="debouncedServicesLoad()" />
                                <select id="svcCategory" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadServices()">
                                    <option value="">All categories</option>
                                    <option value="vtu">VTU</option>
                                    <option value="sms">SMS</option>
                                    <option value="proxy">Proxy</option>
                                </select>
                                <select id="svcStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadServices()">
                                    <option value="">All status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div id="servicesTable" class="p-4"></div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <script>
        let adminToken = localStorage.getItem('admin_token');
        let adminUser = JSON.parse(localStorage.getItem('admin_user') || '{}');
        
        if (!adminToken || !adminUser.role || !['admin', 'super_admin'].includes(adminUser.role)) {
            window.location.href = '/admin/login';
        }
        document.getElementById('userName').textContent = adminUser.name || 'Admin';
        
        async function loadDashboard() {
            try {
                const response = await fetch('/api/admin/dashboard', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                if (response.status === 401) { logout(); return; }
                const data = await response.json();
                if (data.status === 'success') {
                    displayDashboard(data.data);
                } else {
                    showError(data.message || 'Failed to load dashboard');
                }
            } catch (error) {
                showError('Network error. Please try again.');
            }
        }
        
        function displayDashboard(data) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('dashboard').style.display = 'block';
            displayStats(data.stats);
            displayRecentUsers(data.recent_users);
            displayRecentTransactions(data.recent_transactions);
            displayRecentDeposits(data.recent_deposits);
        }
        
        function displayStats(stats) {
            const statsGrid = document.getElementById('statsGrid');
            statsGrid.innerHTML = `
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">Total Users</div>
                    <div class="text-2xl font-bold">${stats.total_users}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">Active Users</div>
                    <div class="text-2xl font-bold">${stats.active_users}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">Total Transactions</div>
                    <div class="text-2xl font-bold">${stats.total_transactions}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">Total Revenue</div>
                    <div class="text-2xl font-bold">₦${parseFloat(stats.total_revenue).toLocaleString()}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">Pending Deposits</div>
                    <div class="text-2xl font-bold">${stats.pending_deposits}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500 text-sm">SMS Orders</div>
                    <div class="text-2xl font-bold">${stats.total_sms_orders}</div>
                </div>
            `;
        }
        
        function displayRecentUsers(users) {
            const container = document.getElementById('recentUsers');
            if (!users.length) { container.innerHTML = '<div class="empty-state">No recent users</div>'; return; }
            container.innerHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 text-slate-600 text-sm">
                            <tr>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Email</th>
                                <th class="px-4 py-2 text-left">Role</th>
                                <th class="px-4 py-2 text-left">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            ${users.map(u => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${u.name}</td>
                                    <td class="px-4 py-2">${u.email}</td>
                                    <td class="px-4 py-2"><span class="status-badge">${u.role}</span></td>
                                    <td class="px-4 py-2">${new Date(u.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                </div>
            `;
        }
        
        function displayRecentTransactions(transactions) {
            const container = document.getElementById('recentTransactions');
            if (!transactions.length) { container.innerHTML = '<div class="empty-state">No recent transactions</div>'; return; }
            container.innerHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 text-slate-600 text-sm">
                            <tr>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left">Type</th>
                                <th class="px-4 py-2 text-left">Amount</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Date</th>
                        </tr>
                    </thead>
                        <tbody class="text-sm">
                        ${transactions.map(tx => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${tx.user ? tx.user.name : 'N/A'}</td>
                                    <td class="px-4 py-2">${tx.type}</td>
                                    <td class="px-4 py-2">₦${parseFloat(tx.amount).toLocaleString()}</td>
                                    <td class="px-4 py-2"><span class="status-badge status-${tx.status}">${tx.status}</span></td>
                                    <td class="px-4 py-2">${new Date(tx.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                </div>
            `;
        }
        
        function displayRecentDeposits(deposits) {
            const container = document.getElementById('recentDeposits');
            if (!deposits.length) { container.innerHTML = '<div class="empty-state">No recent deposits</div>'; return; }
            container.innerHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 text-slate-600 text-sm">
                            <tr>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left">Amount</th>
                                <th class="px-4 py-2 text-left">Reference</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            ${deposits.map(d => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${d.user ? d.user.name : 'N/A'}</td>
                                    <td class="px-4 py-2">₦${parseFloat(d.amount).toLocaleString()}</td>
                                    <td class="px-4 py-2">${d.reference || ''}</td>
                                    <td class="px-4 py-2"><span class="status-badge status-${d.status}">${d.status}</span></td>
                                    <td class="px-4 py-2">${new Date(d.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                </div>
            `;
        }
        
        function showError(message) {
            document.getElementById('loading').style.display = 'none';
            const dash = document.getElementById('dashboard');
            dash.style.display = 'block';
            dash.innerHTML = `<div class="error">${message}</div>`;
        }
        
        function logout() {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_user');
            window.location.href = '/admin/login';
        }
        
        function showUsers() { alert('Users page coming soon!'); }
        function showTransactions() { alert('Transactions page coming soon!'); }
        function showDeposits() { alert('Deposits page coming soon!'); }

        function setTab(name) {
            const overviewSections = ['recentUsersSection','recentTransactionsSection','recentDepositsSection'];
            const showOverview = name === 'overview';
            // Toggle overview sections
            document.getElementById('statsGrid').style.display = showOverview ? 'grid' : 'none';
            overviewSections.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = showOverview ? 'block' : 'none';
            });
            // Toggle other sections
            document.getElementById('smsOrdersSection').style.display = name === 'sms' ? 'block' : 'none';
            document.getElementById('vtuOrdersSection').style.display = name === 'vtu' ? 'block' : 'none';
            document.getElementById('usersSection').style.display = name === 'users' ? 'block' : 'none';
            document.getElementById('depositsSection').style.display = name === 'deposits' ? 'block' : 'none';
            document.getElementById('pricingSection').style.display = name === 'pricing' ? 'block' : 'none';
            document.getElementById('apiServicesSection').style.display = name === 'apiservices' ? 'block' : 'none';
            document.getElementById('transactionsSection').style.display = name === 'transactions' ? 'block' : 'none';
            // nav active state
            document.querySelectorAll('.nav-btn').forEach(btn => {
                const isActive = btn.getAttribute('data-tab') === name;
                btn.classList.toggle('bg-slate-100', isActive);
                btn.classList.toggle('text-indigo-700', isActive);
                btn.classList.toggle('font-semibold', isActive);
            });
            if (name === 'sms') loadSmsOrders();
            if (name === 'vtu') loadVtuOrders();
            if (name === 'users') loadUsers();
            if (name === 'deposits') loadDeposits();
            if (name === 'pricing') loadPricing();
            if (name === 'apiservices') loadServices();
            if (name === 'transactions') loadTransactions();
        }

        async function loadSmsOrders(pageUrl = '/api/admin/orders/sms') {
            const el = document.getElementById('smsOrders');
            el.innerHTML = '<div class="loading">Loading SMS orders...</div>';
            try {
                const res = await fetch(pageUrl, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = (data.data && data.data.data) ? data.data.data : [];
                if (!items.length) { el.innerHTML = '<div class="empty-state">No SMS orders found</div>'; return; }
                el.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">Order</th>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Phone</th>
                                    <th class="px-4 py-2 text-left">Service</th>
                                    <th class="px-4 py-2 text-left">Country</th>
                                    <th class="px-4 py-2 text-left">Provider</th>
                                    <th class="px-4 py-2 text-left">Cost</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Created</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(o => `
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2">${o.order_id}</td>
                                        <td class="px-4 py-2">${o.user ? o.user.name : 'N/A'}</td>
                                        <td class="px-4 py-2">${o.phone_number}</td>
                                        <td class="px-4 py-2">${o.service}</td>
                                        <td class="px-4 py-2">${o.country}</td>
                                        <td class="px-4 py-2">${o.sms_service ? o.sms_service.name : 'N/A'}</td>
                                        <td class="px-4 py-2">${parseFloat(o.cost || 0).toLocaleString()}</td>
                                        <td class="px-4 py-2"><span class="status-badge status-${o.status}">${o.status}</span></td>
                                        <td class="px-4 py-2">${new Date(o.created_at).toLocaleString()}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load SMS orders</div>'; }
        }

        async function loadVtuOrders(pageUrl = '/api/admin/orders/vtu') {
            const el = document.getElementById('vtuOrders');
            el.innerHTML = '<div class="loading">Loading VTU orders...</div>';
            try {
                const res = await fetch(pageUrl, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = (data.data && data.data.data) ? data.data.data : [];
                if (!items.length) { el.innerHTML = '<div class="empty-state">No VTU orders found</div>'; return; }
                el.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">Reference</th>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Type</th>
                                    <th class="px-4 py-2 text-left">Network/Service</th>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Created</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(o => `
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2">${o.reference}</td>
                                        <td class="px-4 py-2">${o.user ? o.user.name : 'N/A'}</td>
                                        <td class="px-4 py-2">${o.type}</td>
                                        <td class="px-4 py-2">${o.network || ''}</td>
                                        <td class="px-4 py-2">${o.phone || ''}</td>
                                        <td class="px-4 py-2">₦${parseFloat(o.amount || 0).toLocaleString()}</td>
                                        <td class="px-4 py-2"><span class="status-badge status-completed">completed</span></td>
                                        <td class="px-4 py-2">${new Date(o.created_at).toLocaleString()}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load VTU orders</div>'; }
        }

        async function loadPricing() {
            const status = document.getElementById('pricingStatus');
            status.textContent = 'Loading...';
            try {
                const res = await fetch('/api/admin/pricing', { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                document.getElementById('markupPercent').value = data.data.markup_percent;
                document.getElementById('currency').value = data.data.currency;
                document.getElementById('autoFx').value = String(data.data.auto_fx);
                status.textContent = '';
            } catch (e) { status.textContent = 'Failed to load pricing.'; }
        }

        async function savePricing(ev) {
            ev.preventDefault();
            const status = document.getElementById('pricingStatus');
            status.textContent = 'Saving...';
            try {
                const payload = {
                    markup_percent: parseFloat(document.getElementById('markupPercent').value || '0'),
                    currency: document.getElementById('currency').value,
                    auto_fx: document.getElementById('autoFx').value === 'true',
                };
                const res = await fetch('/api/admin/pricing', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                status.textContent = data.status === 'success' ? 'Saved.' : (data.message || 'Failed to save.');
            } catch (e) { status.textContent = 'Network error.'; }
            return false;
        }

        async function loadUsers(pageUrl = '/api/admin/users') {
            const el = document.getElementById('usersTable');
            el.innerHTML = '<div class="loading">Loading users...</div>';
            const params = new URLSearchParams();
            const q = document.getElementById('usersSearch')?.value || '';
            const role = document.getElementById('usersRole')?.value || '';
            const status = document.getElementById('usersStatus')?.value || '';
            if (q) params.append('search', q);
            if (role) params.append('role', role);
            if (status) params.append('status', status);
            const url = params.toString() ? `${pageUrl}?${params}` : pageUrl;
            try {
                const res = await fetch(url, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const users = data.data && data.data.data ? data.data.data : [];
                window.__usersCache = users; // cache for instant local filtering
                if (!users.length) { el.innerHTML = '<div class="empty-state">No users found</div>'; return; }
                const rows = users.map(u => `
                    <tr class=\"border-t border-slate-100\"> 
                        <td class=\"px-4 py-2\">${u.name}</td>
                        <td class=\"px-4 py-2\">${u.email}</td>
                        <td class=\"px-4 py-2\">${u.role}</td>
                        <td class=\"px-4 py-2\">₦${parseFloat(u.balance || 0).toLocaleString()}</td>
                        <td class=\"px-4 py-2\">${u.status || ''}</td>
                        <td class=\"px-4 py-2\">${new Date(u.created_at).toLocaleDateString()}</td>
                        <td class=\"px-4 py-2\"><button class=\"text-indigo-600 text-sm\" onclick=\"openUserEdit('${u.id}','${u.name}','${u.email}','${u.role}','${u.status}','${u.balance || 0}')\">Edit</button></td>
                    </tr>
                `).join('');

                const pag = data.data;
                const totalPages = Math.max(1, Math.ceil((pag.total || 0) / (pag.per_page || 1)));
                const base = pageUrl.split('?')[0];
                const pager = `
                    <div class=\"flex items-center justify-between mt-3\"> 
                        <div class=\"text-sm text-slate-500\">Page ${pag.current_page} of ${totalPages} • ${pag.total} users</div>
                        <div class=\"flex items-center gap-2\">
                            <button class=\"px-3 py-1 rounded border\" ${pag.current_page<=1?'disabled':''} onclick=\"loadUsers('${base}?page=${Math.max(1,pag.current_page-1)}')\">Prev</button>
                            <button class=\"px-3 py-1 rounded border\" ${pag.current_page>=totalPages?'disabled':''} onclick=\"loadUsers('${base}?page=${pag.current_page+1}')\">Next</button>
                        </div>
                    </div>`;

                el.innerHTML = `
                    <div class=\"overflow-x-auto\">
                        <table class=\"min-w-full\">
                            <thead class=\"bg-slate-50 text-slate-600 text-sm\">
                                <tr>
                                    <th class=\"px-4 py-2 text-left\">Name</th>
                                    <th class=\"px-4 py-2 text-left\">Email</th>
                                    <th class=\"px-4 py-2 text-left\">Role</th>
                                    <th class=\"px-4 py-2 text-left\">Balance</th>
                                    <th class=\"px-4 py-2 text-left\">Status</th>
                                    <th class=\"px-4 py-2 text-left\">Joined</th>
                                    <th class=\"px-4 py-2 text-left\">Actions</th>
                                </tr>
                            </thead>
                            <tbody class=\"text-sm\">${rows}</tbody>
                        </table>
                        ${pager}
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load users</div>'; }
        }

        let usersLoadTimeout;
        function debouncedUsersLoad() { clearTimeout(usersLoadTimeout); usersLoadTimeout = setTimeout(loadUsers, 300); }

        function filterUsersLocal() {
            const cache = window.__usersCache || [];
            const q = (document.getElementById('usersSearch')?.value || '').toLowerCase();
            const tbody = document.querySelector('#usersTable table tbody');
            if (!tbody) return;
            if (!q) {
                // restore all rows
                Array.from(tbody.rows).forEach(row => row.style.display = '');
                return;
            }
            Array.from(tbody.rows).forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        }

        function openUserEdit(id, name, email, role, status, balance) {
            const amount = prompt(`Adjust balance for ${name} (current ₦${parseFloat(balance||0).toLocaleString()}). Enter amount:`);
            if (!amount) return;
            const action = confirm('OK = Add to balance, Cancel = Subtract from balance') ? 'add' : 'subtract';
            fetch(`/api/admin/users/${id}/balance`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: parseFloat(amount), action })
            }).then(r => r.json()).then(res => {
                if (res.status === 'success') { alert('Balance updated'); loadUsers(); }
                else { alert(res.message || 'Failed to update balance'); }
            }).catch(() => alert('Network error'));
        }

        async function loadServices(pageUrl = '/api/admin/services') {
            const el = document.getElementById('servicesTable');
            el.innerHTML = '<div class="loading">Loading services...</div>';
            const params = new URLSearchParams();
            const q = document.getElementById('svcSearch')?.value || '';
            const cat = document.getElementById('svcCategory')?.value || '';
            const st = document.getElementById('svcStatus')?.value || '';
            if (q) params.append('search', q);
            if (cat) params.append('category', cat);
            if (st !== '') params.append('is_active', st);
            const url = params.toString() ? `${pageUrl}?${params}` : pageUrl;
            try {
                const res = await fetch(url, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = Array.isArray(data.data) ? data.data : [];
                if (!items.length) { el.innerHTML = '<div class="empty-state">No services found</div>'; return; }
                const rows = items.map((s, idx) => `
                    <tr class=\"border-t border-slate-100\" data-row-index=\"${idx}\"> 
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-40\" data-field=\"name\" value=\"${s.name || ''}\" /></td>
                        <td class=\"px-4 py-2\">${s.provider || '-'}</td>
                        <td class=\"px-4 py-2\">${s.type}</td>
                        <td class=\"px-4 py-2\">
                            <select class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm\" data-field=\"is_active\">
                                <option value=\"1\" ${s.is_active ? 'selected' : ''}>Active</option>
                                <option value=\"0\" ${!s.is_active ? 'selected' : ''}>Inactive</option>
                            </select>
                        </td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-28\" data-field=\"balance\" type=\"number\" min=\"0\" step=\"0.01\" value=\"${s.balance || 0}\" /></td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-56\" data-field=\"api_url\" value=\"${s.api_url || ''}\" /></td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-40\" data-field=\"priority\" type=\"number\" min=\"0\" value=\"${s.priority || 0}\" /></td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-56 font-mono\" placeholder=\"${s.api_key_masked || ''}\" data-field=\"api_key\" /></td>
                        ${s.type==='VTU' ? `
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-36\" data-field=\"username\" value=\"${s.username || ''}\" /></td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-28\" type=\"password\" placeholder=\"${s.password_masked || ''}\" data-field=\"password\" /></td>
                        <td class=\"px-4 py-2\"><input class=\"svc-input border border-slate-300 rounded px-2 py-1 text-sm w-20\" type=\"password\" placeholder=\"${s.pin_masked || ''}\" data-field=\"pin\" /></td>
                        ` : '<td></td><td></td><td></td>'}
                        <td class=\"px-4 py-2\"><button class=\"bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded\" onclick=\"saveService(${s.id}, '${s.type}')\">Save</button></td>
                    </tr>
                `).join('');
                el.innerHTML = `
                    <div class=\"overflow-x-auto\"> 
                        <table class=\"min-w-full\"> 
                            <thead class=\"bg-slate-50 text-slate-600 text-sm\"> 
                                <tr>
                                    <th class=\"px-4 py-2 text-left\">Name</th>
                                    <th class=\"px-4 py-2 text-left\">Provider</th>
                                    <th class=\"px-4 py-2 text-left\">Type</th>
                                    <th class=\"px-4 py-2 text-left\">Status</th>
                                    <th class=\"px-4 py-2 text-left\">Balance</th>
                                    <th class=\"px-4 py-2 text-left\">API URL</th>
                                    <th class=\"px-4 py-2 text-left\">Priority</th>
                                    <th class=\"px-4 py-2 text-left\">API Key</th>
                                    <th class=\"px-4 py-2 text-left\">Username</th>
                                    <th class=\"px-4 py-2 text-left\">Password</th>
                                    <th class=\"px-4 py-2 text-left\">PIN</th>
                                    <th class=\"px-4 py-2 text-left\">Actions</th>
                                </tr>
                            </thead>
                            <tbody class=\"text-sm\">${rows}</tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class=\"error\">Failed to load services</div>'; }
        }
        
        let servicesLoadTimeout;
        function debouncedServicesLoad() { clearTimeout(servicesLoadTimeout); servicesLoadTimeout = setTimeout(loadServices, 300); }

        async function loadTransactions(pageUrl = '/api/admin/transactions') {
            const el = document.getElementById('transactionsTable');
            el.innerHTML = '<div class="loading">Loading transactions...</div>';
            const params = new URLSearchParams();
            const q = document.getElementById('txSearch')?.value || '';
            const type = document.getElementById('txType')?.value || '';
            const status = document.getElementById('txStatus')?.value || '';
            const from = document.getElementById('txFrom')?.value || '';
            const to = document.getElementById('txTo')?.value || '';
            const sortBy = document.getElementById('txSortBy')?.value || 'created_at';
            const sortDir = document.getElementById('txSortDir')?.value || 'desc';
            if (q) params.append('search', q);
            if (type) params.append('type', type);
            if (status) params.append('status', status);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);
            if (sortBy) params.append('sort_by', sortBy);
            if (sortDir) params.append('sort_dir', sortDir);
            const url = params.toString() ? `${pageUrl}?${params}` : pageUrl;
            try {
                const res = await fetch(url, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const rowsData = data.data && data.data.data ? data.data.data : [];
                if (!rowsData.length) { el.innerHTML = '<div class="empty-state">No transactions found</div>'; return; }
                const rows = rowsData.map(t => `
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2">${t.user ? (t.user.name || '') : ''}</td>
                        <td class="px-4 py-2">${t.type}</td>
                        <td class="px-4 py-2">₦${parseFloat(t.amount || 0).toLocaleString()}</td>
                        <td class="px-4 py-2">${t.description || ''}</td>
                        <td class="px-4 py-2">${t.reference || ''}</td>
                        <td class="px-4 py-2"><span class="status-badge status-${t.status}">${t.status}</span></td>
                        <td class="px-4 py-2">${new Date(t.created_at).toLocaleString()}</td>
                    </tr>
                `).join('');
                el.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Type</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Description</th>
                                    <th class="px-4 py-2 text-left">Reference</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">${rows}</tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load transactions</div>'; }
        }
        
        function exportTransactions() {
            const params = new URLSearchParams();
            const q = document.getElementById('txSearch')?.value || '';
            const type = document.getElementById('txType')?.value || '';
            const status = document.getElementById('txStatus')?.value || '';
            const from = document.getElementById('txFrom')?.value || '';
            const to = document.getElementById('txTo')?.value || '';
            const sortBy = document.getElementById('txSortBy')?.value || 'created_at';
            const sortDir = document.getElementById('txSortDir')?.value || 'desc';
            if (q) params.append('search', q);
            if (type) params.append('type', type);
            if (status) params.append('status', status);
            if (from) params.append('from_date', from);
            if (to) params.append('to_date', to);
            if (sortBy) params.append('sort_by', sortBy);
            if (sortDir) params.append('sort_dir', sortDir);
            const url = `/api/admin/transactions/export.csv?${params}`;
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.click();
        }

        let transactionsLoadTimeout;
        function debouncedTransactionsLoad() { clearTimeout(transactionsLoadTimeout); transactionsLoadTimeout = setTimeout(loadTransactions, 300); }
        
        function saveService(id, type) {
            // Gather row inputs
            const row = document.querySelector(`tr[data-row-index] button[onclick*="${id}"]`).closest('tr');
            const inputs = row.querySelectorAll('.svc-input');
            const payload = {};
            inputs.forEach(inp => {
                let val = inp.value;
                if (inp.dataset.field === 'is_active') { val = inp.value === '1'; }
                if (inp.type === 'number') { val = parseFloat(val); }
                if (val === '' && (inp.dataset.field === 'api_key' || inp.dataset.field === 'password' || inp.dataset.field === 'pin')) {
                    // skip empty secrets to avoid overwriting
                } else {
                    payload[inp.dataset.field] = val;
                }
            });
            const url = type === 'SMS' ? `/api/admin/services/sms/${id}` : `/api/admin/services/vtu/${id}`;
            fetch(url, { method: 'PUT', headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') { alert('Service updated'); loadServices(); }
                    else { alert(res.message || 'Failed to update'); }
                })
                .catch(() => alert('Network error'));
        }

        async function loadDeposits(pageUrl = '/api/admin/deposits') {
            const el = document.getElementById('depositsTable');
            el.innerHTML = '<div class="loading">Loading deposits...</div>';
            const params = new URLSearchParams();
            const q = document.getElementById('depositsSearch')?.value || '';
            const status = document.getElementById('depositsStatus')?.value || '';
            if (q) params.append('search', q);
            if (status) params.append('status', status);
            const url = params.toString() ? `${pageUrl}?${params}` : pageUrl;
            try {
                const res = await fetch(url, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = data.data && data.data.data ? data.data.data : [];
                if (!items.length) { el.innerHTML = '<div class="empty-state">No deposits found</div>'; return; }
                el.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Reference</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(r => `
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2">${r.user ? r.user.name : 'N/A'}</td>
                                        <td class="px-4 py-2">₦${parseFloat(r.amount || 0).toLocaleString()}</td>
                                        <td class="px-4 py-2">${r.reference || ''}</td>
                                        <td class="px-4 py-2"><span class="status-badge status-${r.status}">${r.status}</span></td>
                                        <td class="px-4 py-2">${new Date(r.created_at).toLocaleString()}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load deposits</div>'; }
        }

        let depositsLoadTimeout;
        function debouncedDepositsLoad() { clearTimeout(depositsLoadTimeout); depositsLoadTimeout = setTimeout(loadDeposits, 300); }

        setTab('overview');
        loadDashboard();
    </script>
</body>
</html>
