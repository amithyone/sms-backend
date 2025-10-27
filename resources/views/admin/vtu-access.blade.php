<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VTU Access Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 md:relative md:translate-x-0 transform -translate-x-full transition-transform duration-300 ease-in-out" id="sidebar">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-200">
                <div class="flex items-center space-x-3">
                    <div class="text-2xl font-bold text-indigo-600">🔆 Fadded VIP</div>
                </div>
            </div>
            
            <nav class="p-4 space-y-2">
                <a href="/admin/dashboard" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/dashboard#sms" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📱</span>
                    <span>SMS Orders</span>
                </a>
                <a href="/admin/dashboard#vtu" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💳</span>
                    <span>VTU Orders</span>
                </a>
                <a href="/admin/dashboard#users" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>👥</span>
                    <span>Users</span>
                </a>
                <a href="/admin/vtu-access" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md bg-slate-100 text-indigo-700 font-semibold">
                    <span>🛡️</span>
                    <span>VTU Access Control</span>
                </a>
                <a href="/admin/advertisements" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📢</span>
                    <span>Advertisements</span>
                </a>
                <a href="/admin/broadcasts" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📣</span>
                    <span>Broadcasts</span>
                </a>
                <a href="/admin/crypto-sales" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>₿</span>
                    <span>Crypto Sales</span>
                </a>
                <a href="/admin/reseller-panels" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>🏪</span>
                    <span>Reseller Panels</span>
                </a>
                <a href="/admin/dashboard#support" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💬</span>
                    <span>Support</span>
                </a>
                <button onclick="logout()" class="w-full flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-red-50 text-red-600 mt-4">
                    <span>🚪</span>
                    <span>Logout</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <span>🛡️</span>
                    VTU Access Management
                </h1>
                <p class="text-gray-600">
                    Control user access to VTU services (Airtime, Data, Electricity, Cable TV, Betting)
                </p>
            </div>

            <!-- Stats Container -->
            <div id="stats-container"></div>

            <!-- Search and Filter -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="searchInput"
                                oninput="updateSearchTerm(this.value)"
                                onkeypress="if(event.key==='Enter') handleSearch()"
                                placeholder="Search by email, name, or phone..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                        <select
                            id="statusSelect"
                            onchange="updateStatusFilter(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Users</option>
                            <option value="enabled">Enabled Only</option>
                            <option value="disabled">Disabled Only</option>
                        </select>
                    </div>
                </div>

                <button
                    onclick="handleSearch()"
                    class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Search
                </button>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VTU Access</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                    <span class="ml-3">Loading users...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="pagination"></div>
            </div>
        </main>
    </div>

    <script>
        const API_BASE_URL = 'https://api.fadsms.com';
        let authToken = localStorage.getItem('admin_token');

        // Check authentication
        if (!authToken) {
            window.location.href = '/admin/login';
        }

        // API helper
        async function apiCall(endpoint, options = {}) {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json',
                    ...options.headers
                }
            });

            if (response.status === 401) {
                localStorage.removeItem('admin_token');
                window.location.href = '/admin/login';
                throw new Error('Unauthorized');
            }

            return response.json();
        }

        // State
        let users = [];
        let stats = null;
        let currentPage = 1;
        let totalPages = 1;
        let searchTerm = '';
        let statusFilter = 'all';

        // Load data
        async function loadStats() {
            try {
                const data = await apiCall('/api/admin/vtu-access/stats');
                if (data.success) {
                    stats = data.data;
                    renderStats();
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        async function loadUsers() {
            try {
                const params = new URLSearchParams();
                if (searchTerm) params.append('search', searchTerm);
                if (statusFilter !== 'all') params.append('status', statusFilter);
                params.append('page', currentPage.toString());
                params.append('per_page', '50');

                const data = await apiCall(`/api/admin/vtu-access/users?${params.toString()}`);
                
                console.log('VTU Access Users Response:', data);
                
                if (data.success && data.data) {
                    users = data.data.data || [];
                    totalPages = data.data.last_page || 1;
                    currentPage = data.data.current_page || 1;
                    renderUsers();
                } else {
                    console.error('API returned error:', data);
                    document.getElementById('users-table').innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-600">API Error: ${data.message || 'Unknown error'}</td></tr>`;
                }
            } catch (error) {
                console.error('Failed to load users:', error);
                document.getElementById('users-table').innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-600">Error: ${error.message}. Check console for details.</td></tr>`;
            }
        }

        async function disableVtuAccess(userId, email) {
            const reason = prompt(`Enter reason for disabling VTU access for ${email}:`);
            if (!reason || !reason.trim()) {
                alert('Reason is required');
                return;
            }

            try {
                const data = await apiCall(`/api/admin/vtu-access/${userId}/disable`, {
                    method: 'POST',
                    body: JSON.stringify({ reason: reason.trim() })
                });

                if (data.success) {
                    alert('VTU access disabled successfully');
                    loadUsers();
                    loadStats();
                } else {
                    alert('Failed: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function enableVtuAccess(userId, email) {
            if (!confirm(`Enable VTU access for ${email}?`)) return;

            try {
                const data = await apiCall(`/api/admin/vtu-access/${userId}/enable`, {
                    method: 'POST'
                });

                if (data.success) {
                    alert('VTU access enabled successfully');
                    loadUsers();
                    loadStats();
                } else {
                    alert('Failed: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        function renderStats() {
            if (!stats) return;

            const statsHtml = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 fade-in">
                    <div class="p-6 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Users</p>
                                <p class="text-3xl font-bold mt-1">${stats.total_users.toLocaleString()}</p>
                            </div>
                            <div class="text-4xl">👥</div>
                        </div>
                    </div>

                    <div class="p-6 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">VTU Access Enabled</p>
                                <p class="text-3xl font-bold mt-1 text-green-600">${stats.enabled_users.toLocaleString()}</p>
                            </div>
                            <div class="text-4xl">✅</div>
                        </div>
                    </div>

                    <div class="p-6 bg-white rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">VTU Access Disabled</p>
                                <p class="text-3xl font-bold mt-1 text-red-600">${stats.disabled_users.toLocaleString()}</p>
                            </div>
                            <div class="text-4xl">🚫</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('stats-container').innerHTML = statsHtml;
        }

        function renderUsers() {
            const usersHtml = users.map(user => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <div class="font-medium text-gray-900">${user.name || 'Unknown'}</div>
                            <div class="text-sm text-gray-600">${user.email}</div>
                            ${user.phone ? `<div class="text-xs text-gray-500">${user.phone}</div>` : ''}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-semibold text-green-600">₦${user.balance.toLocaleString()}</span>
                    </td>
                    <td class="px-6 py-4">
                        ${user.vtu_access_enabled 
                            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✅ Enabled</span>'
                            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">🚫 Disabled</span>'
                        }
                    </td>
                    <td class="px-6 py-4 text-sm">
                        ${user.vtu_access_reason 
                            ? `<div>
                                <div class="text-gray-900">${user.vtu_access_reason}</div>
                                ${user.vtu_access_disabled_at ? `<div class="text-xs text-gray-500 mt-1">📅 ${new Date(user.vtu_access_disabled_at).toLocaleDateString()}</div>` : ''}
                               </div>`
                            : '<span class="text-gray-400">-</span>'
                        }
                    </td>
                    <td class="px-6 py-4">
                        ${user.vtu_access_enabled
                            ? `<button onclick="disableVtuAccess(${user.id}, '${user.email}')" 
                                 class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors">
                                 🚫 Disable
                               </button>`
                            : `<button onclick="enableVtuAccess(${user.id}, '${user.email}')" 
                                 class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                 ✅ Enable
                               </button>`
                        }
                    </td>
                </tr>
            `).join('');

            const paginationHtml = totalPages > 1 ? `
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <button 
                        onclick="changePage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''}
                        class="px-4 py-2 rounded ${currentPage === 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'}">
                        Previous
                    </button>
                    <span class="text-gray-700">Page ${currentPage} of ${totalPages}</span>
                    <button 
                        onclick="changePage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''}
                        class="px-4 py-2 rounded ${currentPage === totalPages ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'}">
                        Next
                    </button>
                </div>
            ` : '';

            document.getElementById('users-table').innerHTML = usersHtml || '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No users found</td></tr>';
            document.getElementById('pagination').innerHTML = paginationHtml;
        }

        function changePage(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            loadUsers();
        }

        function handleSearch() {
            currentPage = 1;
            loadUsers();
        }

        function updateSearchTerm(value) {
            searchTerm = value;
        }

        function updateStatusFilter(value) {
            statusFilter = value;
            currentPage = 1;
            loadUsers();
        }

        function logout() {
            localStorage.removeItem('admin_token');
            window.location.href = '/admin/login';
        }

        // Initialize
        loadStats();
        loadUsers();
    </script>
</body>
</html>
