<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Notifications - Admin Panel</title>
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
                <a href="/admin/dashboard#support" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💬</span>
                    <span>Support</span>
                </a>
                <a href="/admin/advertisements" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📢</span>
                    <span>Advertisements</span>
                </a>
                <a href="/admin/broadcasts" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md bg-slate-100 text-indigo-700 font-semibold">
                    <span>📣</span>
                    <span>Broadcasts</span>
                </a>
                <a href="/admin/crypto-sales" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💰</span>
                    <span>Crypto Sales</span>
                </a>
                <a href="/admin/reseller-panels" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>🚀</span>
                    <span>Child Panels</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-0">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">📣 Broadcast Notifications</h1>
                        <p class="text-slate-600">Send updates and announcements to all users</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600" id="userName">Loading...</span>
                        <button onclick="logout()" class="bg-rose-500 hover:bg-rose-600 text-white text-sm px-3 py-1.5 rounded-md">Logout</button>
                    </div>
                </div>
            </header>

            <div class="p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="statsContainer">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Broadcasts</div>
                    <div class="text-3xl font-bold text-gray-900" id="totalBroadcasts">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Sent</div>
                    <div class="text-3xl font-bold text-green-600" id="sentBroadcasts">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Pending</div>
                    <div class="text-3xl font-bold text-yellow-600" id="pendingBroadcasts">0</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-sm text-gray-600 mb-1">Total Delivered</div>
                    <div class="text-3xl font-bold text-blue-600" id="totalDelivered">0</div>
                </div>
            </div>

            <!-- Create New Broadcast Form -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📝 Create New Broadcast</h2>
                
                <form id="broadcastForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" id="title" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Notification title">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select id="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="info">ℹ️ Info</option>
                                <option value="success">✅ Success</option>
                                <option value="warning">⚠️ Warning</option>
                                <option value="error">❌ Error</option>
                                <option value="update">🔔 Update</option>
                                <option value="promo">🎁 Promotion</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                        <textarea id="message" required rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter your message to all users..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                            <select id="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">🔴 Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                            <select id="targetAudience" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="all" selected>All Users</option>
                                <option value="active">Active Users Only</option>
                                <option value="inactive">Inactive Users</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Action Button (Optional)</label>
                            <input type="text" id="actionText"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., Learn More">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Action URL (Optional)</label>
                        <input type="url" id="actionUrl"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="https://example.com">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="resetForm()"
                            class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                            📢 Send Broadcast Now
                        </button>
                    </div>
                </form>
            </div>

            <!-- Broadcast History -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">📋 Broadcast History</h2>
                </div>
                
                <div id="broadcastsList" class="divide-y divide-gray-200">
                    <!-- Broadcasts will be loaded here -->
                    <div class="p-8 text-center text-gray-500">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-3"></div>
                        Loading broadcasts...
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const API_BASE = 'https://api.fadsms.com/api/admin';
        let authToken = localStorage.getItem('admin_token');

        // Check authentication
        if (!authToken) {
            window.location.href = '/admin/login';
        }

        // Load user info
        async function loadUserInfo() {
            try {
                const response = await fetch('https://api.fadsms.com/api/admin/dashboard', {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.status === 'success' && data.data.user) {
                    document.getElementById('userName').textContent = data.data.user.name;
                }
            } catch (error) {
                console.error('Failed to load user info:', error);
            }
        }

        // Logout function
        function logout() {
            localStorage.removeItem('admin_token');
            window.location.href = '/admin/login';
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadUserInfo();
            loadStats();
            loadBroadcasts();
        });

        // Load statistics
        async function loadStats() {
            try {
                const response = await fetch(`${API_BASE}/broadcasts/stats`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.status === 'success') {
                    document.getElementById('totalBroadcasts').textContent = data.data.total_broadcasts;
                    document.getElementById('sentBroadcasts').textContent = data.data.sent;
                    document.getElementById('pendingBroadcasts').textContent = data.data.pending;
                    document.getElementById('totalDelivered').textContent = data.data.total_delivered.toLocaleString();
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        // Load broadcasts list
        async function loadBroadcasts() {
            try {
                const response = await fetch(`${API_BASE}/broadcasts`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                
                if (data.status === 'success' && data.data.data) {
                    renderBroadcasts(data.data.data);
                }
            } catch (error) {
                console.error('Failed to load broadcasts:', error);
                document.getElementById('broadcastsList').innerHTML = 
                    '<div class="p-8 text-center text-red-600">Failed to load broadcasts</div>';
            }
        }

        // Render broadcasts list
        function renderBroadcasts(broadcasts) {
            const container = document.getElementById('broadcastsList');
            
            if (broadcasts.length === 0) {
                container.innerHTML = '<div class="p-8 text-center text-gray-500">No broadcasts yet</div>';
                return;
            }
            
            container.innerHTML = broadcasts.map(broadcast => `
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">${broadcast.title}</h3>
                                <span class="px-2 py-1 text-xs rounded-full ${getTypeColor(broadcast.type)}">
                                    ${getTypeLabel(broadcast.type)}
                                </span>
                                <span class="px-2 py-1 text-xs rounded-full ${broadcast.is_sent ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                                    ${broadcast.is_sent ? 'Sent' : 'Pending'}
                                </span>
                            </div>
                            <p class="text-gray-600 mb-3">${broadcast.message}</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span>👥 ${broadcast.total_recipients} recipients</span>
                                <span>✅ ${broadcast.delivered_count} delivered</span>
                                <span>📅 ${new Date(broadcast.created_at).toLocaleString()}</span>
                                <span>👤 ${broadcast.admin?.name || 'Admin'}</span>
                            </div>
                        </div>
                        <div class="ml-4 space-y-2">
                            ${!broadcast.is_sent ? `
                                <button onclick="sendBroadcast(${broadcast.id})"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                                    📤 Send Now
                                </button>
                            ` : ''}
                            <button onclick="deleteBroadcast(${broadcast.id})"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Type colors
        function getTypeColor(type) {
            const colors = {
                'info': 'bg-blue-100 text-blue-700',
                'success': 'bg-green-100 text-green-700',
                'warning': 'bg-yellow-100 text-yellow-700',
                'error': 'bg-red-100 text-red-700',
                'update': 'bg-purple-100 text-purple-700',
                'promo': 'bg-pink-100 text-pink-700'
            };
            return colors[type] || colors.info;
        }

        // Type labels
        function getTypeLabel(type) {
            const labels = {
                'info': 'ℹ️ Info',
                'success': '✅ Success',
                'warning': '⚠️ Warning',
                'error': '❌ Error',
                'update': '🔔 Update',
                'promo': '🎁 Promo'
            };
            return labels[type] || labels.info;
        }

        // Submit form
        document.getElementById('broadcastForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                title: document.getElementById('title').value,
                message: document.getElementById('message').value,
                type: document.getElementById('type').value,
                priority: document.getElementById('priority').value,
                target_audience: document.getElementById('targetAudience').value,
                action_text: document.getElementById('actionText').value || null,
                action_url: document.getElementById('actionUrl').value || null,
                send_now: true
            };
            
            try {
                const response = await fetch(`${API_BASE}/broadcasts`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Broadcast sent successfully to ' + data.data.total_recipients + ' users!');
                    resetForm();
                    loadStats();
                    loadBroadcasts();
                } else {
                    alert('❌ Failed to send broadcast: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error sending broadcast:', error);
                alert('❌ Failed to send broadcast. Please try again.');
            }
        });

        // Reset form
        function resetForm() {
            document.getElementById('broadcastForm').reset();
        }

        // Send a pending broadcast
        async function sendBroadcast(id) {
            if (!confirm('Are you sure you want to send this broadcast?')) return;
            
            try {
                const response = await fetch(`${API_BASE}/broadcasts/${id}/send`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Broadcast sent successfully!');
                    loadStats();
                    loadBroadcasts();
                } else {
                    alert('❌ Failed to send broadcast: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error sending broadcast:', error);
                alert('❌ Failed to send broadcast. Please try again.');
            }
        }

        // Delete broadcast
        async function deleteBroadcast(id) {
            if (!confirm('Are you sure you want to delete this broadcast?')) return;
            
            try {
                const response = await fetch(`${API_BASE}/broadcasts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('✅ Broadcast deleted successfully!');
                    loadStats();
                    loadBroadcasts();
                } else {
                    alert('❌ Failed to delete broadcast: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting broadcast:', error);
                alert('❌ Failed to delete broadcast. Please try again.');
            }
        }
    </script>
</body>
</html>

