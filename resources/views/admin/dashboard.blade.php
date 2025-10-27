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
        .status-success { background: #dcfce7; color: #166534; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f1f5f9; color: #475569; }
        .status-suspended { background: #fee2e2; color: #991b1b; }
        .status-open { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-resolved { background: #dcfce7; color: #166534; }
        .status-closed { background: #f1f5f9; color: #475569; }
        
        /* Mobile sidebar styles */
        .mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        .mobile-sidebar.open {
            transform: translateX(0);
        }
        .mobile-overlay {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .mobile-overlay.open {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-700">
    <div class="flex min-h-screen">
        <!-- Mobile Sidebar Overlay -->
        <div id="mobileSidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleMobileSidebar()"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:relative inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <div class="text-indigo-600 font-bold text-xl">🔆 Fadded VIP</div>
                    <div class="text-xs text-slate-500">Admin Panel</div>
                </div>
                <button class="md:hidden p-2 rounded-md hover:bg-slate-100" onclick="toggleMobileSidebar()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav class="p-4 space-y-1">
                <button data-tab="overview" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('overview')">📊 Dashboard</button>
                <button data-tab="sms" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('sms')">📱 SMS Orders</button>
                <button data-tab="vtu" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('vtu')">💳 VTU Orders</button>
                <button data-tab="users" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('users')">👥 Users</button>
                <button data-tab="deposits" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('deposits')">💰 Deposits</button>
                <button data-tab="transactions" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('transactions')">📋 Transactions</button>
                <button data-tab="support" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('support')">💬 Support Tickets</button>
                <button data-tab="v2migration" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('v2migration')">🔄 V2 Migration</button>
                <button data-tab="pricing" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('pricing')">⚙️ Pricing</button>
                <button data-tab="apiservices" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('apiservices')">🔧 API</button>
                <a href="/admin/advertisements" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">📢 Advertisements</a>
                <a href="/admin/broadcasts" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">📣 Broadcasts</a>
                <a href="/admin/crypto-sales" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">💰 Crypto Sales</a>
                <a href="/admin/reseller-panels" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">🚀 Child Panels</a>
                <a href="/admin/vtu-access" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">🛡️ VTU Access Control</a>
            </nav>
        </aside>

        <!-- Mobile Sidebar -->
        <aside id="mobileSidebar" class="mobile-sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 md:hidden">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div class="text-indigo-600 font-bold text-xl">🔆 Fadded VIP</div>
                <button onclick="toggleMobileSidebar()" class="p-2 hover:bg-slate-100 rounded-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav class="p-4 space-y-1">
                <button data-tab="overview" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('overview'); toggleMobileSidebar()">Dashboard</button>
                <button data-tab="sms" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('sms'); toggleMobileSidebar()">SMS Orders</button>
                <button data-tab="vtu" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('vtu'); toggleMobileSidebar()">VTU Orders</button>
                <button data-tab="users" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('users'); toggleMobileSidebar()">Users</button>
                <button data-tab="deposits" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('deposits'); toggleMobileSidebar()">Deposits</button>
                <button data-tab="transactions" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('transactions'); toggleMobileSidebar()">Transactions</button>
                <button data-tab="support" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('support'); toggleMobileSidebar()">💬 Support Tickets</button>
                <button data-tab="v2migration" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('v2migration'); toggleMobileSidebar()">V2 Migration</button>
                <button data-tab="pricing" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('pricing'); toggleMobileSidebar()">Pricing</button>
                <button data-tab="apiservices" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100" onclick="setTab('apiservices'); toggleMobileSidebar()">API</button>
                <a href="/admin/advertisements" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">Advertisements</a>
                <a href="/admin/broadcasts" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">Broadcasts</a>
                <a href="/admin/crypto-sales" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">Crypto Sales</a>
                <a href="/admin/reseller-panels" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">Child Panels</a>
                <a href="/admin/vtu-access" class="nav-btn w-full text-left px-3 py-2 rounded-md hover:bg-slate-100 block">🛡️ VTU Access</a>
            </nav>
        </aside>

        <!-- Mobile Overlay -->
        <div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden" onclick="toggleMobileSidebar()"></div>

        <!-- Main -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-4 md:px-6 py-3 md:py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button onclick="toggleMobileSidebar()" class="md:hidden p-2 hover:bg-slate-100 rounded-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="md:hidden">
                        <div class="text-indigo-600 font-bold text-lg">🔆 Fadded VIP</div>
                    </div>
                </div>
                <div class="flex-1 md:px-4">
                    <div class="text-base md:text-lg font-semibold">Admin Dashboard</div>
                    <div class="text-xs text-slate-500 hidden sm:block">Overview and management</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm hidden sm:block" id="userName">Loading...</span>
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
                            <div class="flex items-center justify-between">
                                <h2 class="font-semibold">SMS / Virtual Number Orders</h2>
                                <div class="flex items-center space-x-2">
                                    <!-- Quick Search -->
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            id="smsOrdersSearch" 
                                            placeholder="Search orders, users, phones, services..." 
                                            class="pl-8 pr-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            onkeyup="searchSmsOrders(this.value)"
                                        />
                                        <svg class="absolute left-2.5 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <!-- Filter Dropdown -->
                                    <select id="smsOrdersFilter" onchange="filterSmsOrders(this.value)" class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="expired">Expired</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
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
                        <div class="px-4 py-3 border-b border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="font-semibold">Users</h2>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input id="usersSearch" type="text" placeholder="Search users..." class="flex-1 border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="filterUsersLocal(); debouncedUsersLoad()" />
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
                        <div class="px-4 py-3 border-b border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="font-semibold">Deposits</h2>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input id="depositsSearch" type="text" placeholder="Search reference/user..." class="flex-1 border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="debouncedDepositsLoad()" />
                                <select id="depositsStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadDeposits()">
                                    <option value="">All status</option>
                                    <option value="pending">⏳ Pending</option>
                                    <option value="completed">✅ Completed</option>
                                    <option value="failed">❌ Failed</option>
                                    <option value="cancelled">⚫ Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div id="depositsTable" class="p-4"></div>
                    </section>

                    <!-- Transactions Page -->
                    <section id="transactionsSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="font-semibold">Transactions</h2>
                                <a id="txExport" href="#" class="text-sm text-indigo-600" onclick="exportTransactions()">Export CSV</a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
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
                            </div>
                        </div>
                        <div id="transactionsTable" class="p-4"></div>
                    </section>

                    <!-- Pricing -->
                    <section id="pricingSection" style="display:none;" class="space-y-6">
                        <!-- Current Price Mapping Display -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <h2 class="font-semibold">📊 Current Price Mapping</h2>
                                <p class="text-xs text-slate-500 mt-1">Real-time view of current pricing configuration and exchange rates</p>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- Exchange Rate Display -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-sm font-medium text-blue-900">💱 USD to NGN Rate</h3>
                                                <p class="text-xs text-blue-600 mt-1">Current exchange rate</p>
                                            </div>
                                            <div class="text-right">
                                                <div id="currentFxRate" class="text-2xl font-bold text-blue-900">₦1,600</div>
                                                <div class="text-xs text-blue-600">per $1 USD</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-xs text-blue-700">
                                            <span id="fxLastUpdated">Last updated: Just now</span>
                                        </div>
                                    </div>

                                    <!-- Profit Margin Display -->
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-sm font-medium text-green-900">💰 Profit Margin</h3>
                                                <p class="text-xs text-green-600 mt-1">Current profit percentage</p>
                                            </div>
                                            <div class="text-right">
                                                <div id="currentProfitMargin" class="text-2xl font-bold text-green-900">15%</div>
                                                <div class="text-xs text-green-600">on final price</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-xs text-green-700">
                                            <span>Applied after minimum price</span>
                                        </div>
                                    </div>

                                    <!-- Pricing Summary -->
                                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-sm font-medium text-purple-900">📈 Pricing Summary</h3>
                                                <p class="text-xs text-purple-600 mt-1">Current configuration</p>
                                            </div>
                                            <div class="text-right">
                                                <div id="currentMinPrice" class="text-lg font-bold text-purple-900">₦1,500</div>
                                                <div class="text-xs text-purple-600">minimum price</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-xs text-purple-700">
                                            <span id="currentMarkup">Base markup: 10%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Calculation Example -->
                                <div class="mt-6 bg-slate-50 border border-slate-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-slate-700">🧮 Live Price Calculation Example</h4>
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-slate-600">Provider Cost (USD):</label>
                                            <input id="exampleProviderCostInput" type="number" min="0.01" max="10" step="0.01" value="0.50" 
                                                   class="w-20 text-xs border border-slate-300 rounded px-2 py-1" 
                                                   onchange="updateExampleProviderCost()" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Provider Cost (USD):</span>
                                                <span id="exampleProviderCost" class="font-medium">$0.50</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Converted to NGN:</span>
                                                <span id="exampleConverted" class="font-medium">₦800</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600" data-step="markup">+ Base Markup (10%):</span>
                                                <span id="exampleWithMarkup" class="font-medium">₦880</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">+ VAT/Fixed Fee:</span>
                                                <span id="exampleWithVat" class="font-medium">₦1,580</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Minimum Price Check:</span>
                                                <span id="exampleMinCheck" class="font-medium">₦1,500 ✓</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600" data-step="profit">+ Profit Margin (15%):</span>
                                                <span id="exampleWithProfit" class="font-medium">₦1,817</span>
                                            </div>
                                            <div class="flex justify-between text-sm font-semibold border-t border-slate-300 pt-2">
                                                <span class="text-slate-800">Final Customer Price:</span>
                                                <span id="exampleFinalPrice" class="text-green-600">₦1,817</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Refresh Button -->
                                <div class="mt-4 flex justify-end">
                                    <button onclick="refreshPriceMapping()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md flex items-center gap-2">
                                        <span>🔄</span>
                                        Refresh Price Mapping
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- VTU Pricing Settings -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <h2 class="font-semibold">VTU Pricing Settings</h2>
                                <p class="text-xs text-slate-500 mt-1">Pricing for Airtime, Data, Electricity, and Betting services</p>
                            </div>
                            <div class="p-4">
                                <form id="vtuPricingForm" onsubmit="return saveVtuPricing(event)" class="space-y-4">
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
                                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md" type="submit">Save VTU Settings</button>
                                        <span id="vtuPricingStatus" class="ml-2 text-sm text-slate-500"></span>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- SMS Pricing Settings -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <h2 class="font-semibold">SMS Virtual Number Pricing</h2>
                                <p class="text-xs text-slate-500 mt-1">Configure pricing, profit margins, and exchange rates for SMS services</p>
                            </div>
                            <div class="p-4">
                                <form id="smsPricingForm" onsubmit="return saveSmsPricing(event)" class="space-y-6">
                                    <!-- FX Rate -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <label class="block">
                                            <span class="text-sm font-medium text-slate-700">💱 USD to NGN Exchange Rate</span>
                                            <p class="text-xs text-slate-500 mb-2">How much NGN per 1 USD (e.g., 1600 means $1 = ₦1600)</p>
                                            <input id="smsFxRate" type="number" min="1000" max="3000" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="1600" />
                                        </label>
                                    </div>

                                    <!-- Profit Margin -->
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <label class="block">
                                            <span class="text-sm font-medium text-slate-700">💰 Profit Margin (%)</span>
                                            <p class="text-xs text-slate-500 mb-2">Percentage added ON TOP of final price after minimum enforcement (e.g., 15% means ₦1500 becomes ₦1725)</p>
                                            <input id="smsProfitMargin" type="number" min="0" max="100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="15" />
                                            <p class="text-xs text-blue-600 mt-1">Example: If service costs ₦1500, with 15% profit margin = ₦1725 charged to customer</p>
                                        </label>
                                    </div>

                                    <!-- Advanced Settings -->
                                    <div class="border border-slate-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="text-sm font-medium text-slate-700">Advanced Settings</h3>
                                            <div class="flex items-center gap-2 text-xs text-blue-600">
                                                <span>🔄</span>
                                                <span>Live preview enabled</span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <label class="block">
                                                <span class="text-sm text-slate-600">Minimum Price (₦)</span>
                                                <p class="text-xs text-slate-500 mb-1">Minimum price before profit margin</p>
                                                <input id="smsMinPrice" type="number" min="100" max="10000" step="50" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="1500" />
                                            </label>
                                            <label class="block">
                                                <span class="text-sm text-slate-600">Base Markup (%)</span>
                                                <p class="text-xs text-slate-500 mb-1">Initial markup on provider cost</p>
                                                <input id="smsMarkup" type="number" min="0" max="100" step="1" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="10" />
                                            </label>
                                            <label class="block">
                                                <span class="text-sm text-slate-600">VAT/Fixed Fee (₦)</span>
                                                <p class="text-xs text-slate-500 mb-1">Fixed amount added to cost</p>
                                                <input id="smsVat" type="number" min="0" max="5000" step="50" class="w-full border border-slate-300 rounded-md px-3 py-2" placeholder="700" />
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Pricing Formula Explanation -->
                                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-slate-700 mb-2">📊 Pricing Formula</h4>
                                        <ol class="text-xs text-slate-600 space-y-1 list-decimal list-inside">
                                            <li>Convert provider cost (USD) to NGN using exchange rate</li>
                                            <li>Apply base markup percentage</li>
                                            <li>Add VAT/fixed fee</li>
                                            <li>Enforce minimum price (₦1500)</li>
                                            <li><strong class="text-green-600">Apply profit margin % on final price</strong> ← NEW!</li>
                                        </ol>
                                        <p class="text-xs text-blue-600 mt-2"><strong>Example:</strong> Provider charges $0.88 → ₦1408 → After minimum ₦1500 → <span class="text-green-600">+15% profit = ₦1725 to customer</span></p>
                                    </div>

                                    <div>
                                        <button class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-md" type="submit">💾 Save SMS Pricing</button>
                                        <span id="smsPricingStatus" class="ml-2 text-sm text-slate-500"></span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <!-- V2 Migration Management -->
                    <section id="v2MigrationSection" style="display:none;" class="space-y-6">
                        <!-- Status Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white rounded-xl border border-slate-200 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-slate-900">Sync Status</h3>
                                    <button onclick="loadV2Status()" class="text-indigo-600 text-sm hover:text-indigo-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div id="v2StatusContent" class="space-y-3"></div>
                            </div>
                            
                            <div class="bg-white rounded-xl border border-slate-200 p-6">
                                <h3 class="font-semibold text-slate-900 mb-4">Statistics</h3>
                                <div id="v2StatsContent" class="space-y-3"></div>
                            </div>
                        </div>

                        <!-- API Key Management -->
                        <div class="bg-white rounded-xl border border-slate-200 p-6">
                            <h3 class="font-semibold text-slate-900 mb-4">API Key Management</h3>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <div class="text-sm text-slate-600 mb-1">Current API Key</div>
                                    <code id="v2ApiKeyDisplay" class="block bg-slate-50 px-3 py-2 rounded text-sm font-mono">Loading...</code>
                                </div>
                                <button onclick="regenerateV2ApiKey()" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Regenerate Key
                                </button>
                            </div>
                        </div>

                        <!-- Migration Logs -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="font-semibold">Migration Logs</h3>
                                <button onclick="loadV2Logs()" class="text-indigo-600 text-sm hover:text-indigo-700">Refresh</button>
                            </div>
                            <div id="v2LogsContent" class="p-4"></div>
                        </div>

                        <!-- Recent Syncs -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <h3 class="font-semibold">Recent Sync Activity</h3>
                            </div>
                            <div id="v2RecentSyncs" class="p-4"></div>
                        </div>
                    </section>

                    <!-- Support Tickets Management -->
                    <section id="supportSection" style="display:none;" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="font-semibold">Support Tickets</h2>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <select id="supportStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadSupportTickets()">
                                    <option value="">All status</option>
                                    <option value="open">🟡 Open</option>
                                    <option value="in_progress">🔵 In Progress</option>
                                    <option value="resolved">🟢 Resolved</option>
                                    <option value="closed">⚫ Closed</option>
                                </select>
                                <select id="supportPriority" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadSupportTickets()">
                                    <option value="">All priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">🔴 Urgent</option>
                                </select>
                                <select id="supportCategory" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadSupportTickets()">
                                    <option value="">All categories</option>
                                    <option value="general">General</option>
                                    <option value="payment">💰 Payment</option>
                                    <option value="service">📱 Service</option>
                                    <option value="technical">🔧 Technical</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div id="supportTicketsTable" class="p-4"></div>
                    </section>

                    <!-- API Management -->
                    <section id="apiServicesSection" style="display:none;" class="space-y-6">
                        <!-- Provider Balances -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="font-semibold">Provider Balances</h2>
                                    <button onclick="loadProviderBalances()" class="text-indigo-600 text-sm hover:text-indigo-700 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Refresh
                                    </button>
                                </div>
                            </div>
                            <div id="providerBalances" class="p-4">
                                <div class="text-sm text-slate-500 text-center py-8">Loading provider balances...</div>
                            </div>
                        </div>

                        <!-- API Services -->
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-200">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="font-semibold">API Services</h2>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input id="svcSearch" type="text" placeholder="Search services..." class="flex-1 border border-slate-300 rounded-md px-3 py-1.5 text-sm" onkeyup="debouncedServicesLoad()" />
                                    <select id="svcCategory" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadServices()">
                                        <option value="">All categories</option>
                                        <option value="vtu">Fadded VIP 🔆 VTU</option>
                                        <option value="sms">Fadded VIP 🔆 SMS</option>
                                        <option value="proxy">Fadded VIP 🔆 Proxy</option>
                                    </select>
                                    <select id="svcStatus" class="border border-slate-300 rounded-md px-2 py-1.5 text-sm" onchange="loadServices()">
                                        <option value="">All status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div id="servicesTable" class="p-4">
                                <div class="text-sm text-slate-500 text-center py-8">Loading API services...</div>
                            </div>
                        </div>
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
                    <div class="text-slate-500 text-sm">Total User Balance</div>
                    <div class="text-2xl font-bold">₦${parseFloat(stats.total_user_balance ?? 0).toLocaleString()}</div>
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
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Email</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Role</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            ${users.map(u => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${u.name}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${u.email}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell"><span class="status-badge">${u.role}</span></td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${new Date(u.created_at).toLocaleDateString()}</td>
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
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Status</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Date</th>
                        </tr>
                    </thead>
                        <tbody class="text-sm">
                        ${transactions.map(tx => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${tx.user ? tx.user.name : 'N/A'}</td>
                                    <td class="px-4 py-2">${tx.type}</td>
                                    <td class="px-4 py-2">₦${parseFloat(tx.amount).toLocaleString()}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell"><span class="status-badge status-${tx.status}">${tx.status}</span></td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${new Date(tx.created_at).toLocaleDateString()}</td>
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
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Reference</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Status</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Date</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            ${deposits.map(d => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${d.user ? d.user.name : 'N/A'}</td>
                                    <td class="px-4 py-2">₦${parseFloat(d.amount).toLocaleString()}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${d.reference || ''}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell"><span class="status-badge status-${d.status}">${d.status}</span></td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${new Date(d.created_at).toLocaleDateString()}</td>
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
        
        function showUsers() { showToast('Users page coming soon!', 'info'); }
        function showTransactions() { showToast('Transactions page coming soon!', 'info'); }
        function showDeposits() { showToast('Deposits page coming soon!', 'info'); }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        }

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
            document.getElementById('supportSection').style.display = name === 'support' ? 'block' : 'none';
            document.getElementById('pricingSection').style.display = name === 'pricing' ? 'block' : 'none';
            document.getElementById('v2MigrationSection').style.display = name === 'v2migration' ? 'block' : 'none';
            document.getElementById('apiServicesSection').style.display = name === 'apiservices' ? 'block' : 'none';
            document.getElementById('transactionsSection').style.display = name === 'transactions' ? 'block' : 'none';
            
            // Load data when switching to specific tabs
            if (name === 'apiservices') {
                loadProviderBalances();
                loadServices();
            } else if (name === 'sms') {
                loadSmsOrders();
            } else if (name === 'vtu') {
                loadVtuOrders();
            } else if (name === 'users') {
                loadUsers();
            } else if (name === 'deposits') {
                loadDeposits();
            } else if (name === 'support') {
                loadSupportTickets();
            } else if (name === 'v2migration') {
                loadV2Migration();
            }
            
            // nav active state
            document.querySelectorAll('.nav-btn').forEach(btn => {
                const isActive = btn.getAttribute('data-tab') === name;
                btn.classList.toggle('bg-slate-100', isActive);
                btn.classList.toggle('text-indigo-700', isActive);
                btn.classList.toggle('font-semibold', isActive);
            });
        }

        // Store original SMS orders data for filtering
        let originalSmsOrders = [];
        let currentSmsOrdersFilter = '';

        async function loadSmsOrders(pageUrl = '/api/admin/orders/sms') {
            const el = document.getElementById('smsOrders');
            el.innerHTML = '<div class="loading">Loading SMS orders...</div>';
            try {
                const res = await fetch(pageUrl, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = (data.data && data.data.data) ? data.data.data : [];
                originalSmsOrders = items; // Store original data
                if (!items.length) { el.innerHTML = '<div class="empty-state">No SMS orders found</div>'; return; }
                renderSmsOrders(items);
            } catch (e) { el.innerHTML = '<div class="error">Failed to load SMS orders</div>'; }
        }

        function renderSmsOrders(items) {
            const el = document.getElementById('smsOrders');
            if (!items.length) { 
                el.innerHTML = '<div class="empty-state">No SMS orders found</div>'; 
                return; 
            }
            el.innerHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50 text-slate-600 text-sm">
                            <tr>
                                <th class="px-4 py-2 text-left">Order</th>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Phone</th>
                                <th class="px-4 py-2 text-left hidden md:table-cell">Service</th>
                                <th class="px-4 py-2 text-left hidden md:table-cell">Country</th>
                                <th class="px-4 py-2 text-left hidden lg:table-cell">Provider</th>
                                <th class="px-4 py-2 text-left">Cost</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Status</th>
                                <th class="px-4 py-2 text-left hidden sm:table-cell">Created</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            ${items.map(o => `
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-2">${o.order_id}</td>
                                    <td class="px-4 py-2">${o.user ? o.user.name : 'N/A'}</td>
                                <td class="px-4 py-2 hidden sm:table-cell">${o.phone_number}</td>
                                <td class="px-4 py-2 hidden md:table-cell">${o.service}</td>
                                <td class="px-4 py-2 hidden md:table-cell">${o.country}</td>
                                <td class="px-4 py-2 hidden lg:table-cell">${o.sms_service ? o.sms_service.name : 'N/A'}</td>
                                    <td class="px-4 py-2">${parseFloat(o.cost || 0).toLocaleString()}</td>
                                <td class="px-4 py-2 hidden sm:table-cell"><span class="status-badge status-${o.status}">${o.status}</span></td>
                                <td class="px-4 py-2 hidden sm:table-cell">${new Date(o.created_at).toLocaleString()}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function searchSmsOrders(searchTerm) {
            if (!originalSmsOrders.length) return;
            
            const filtered = originalSmsOrders.filter(order => {
                const searchLower = searchTerm.toLowerCase();
                return (
                    (order.order_id && order.order_id.toLowerCase().includes(searchLower)) ||
                    (order.user && order.user.name && order.user.name.toLowerCase().includes(searchLower)) ||
                    (order.phone_number && order.phone_number.toLowerCase().includes(searchLower)) ||
                    (order.service && order.service.toLowerCase().includes(searchLower)) ||
                    (order.country && order.country.toLowerCase().includes(searchLower)) ||
                    (order.sms_service && order.sms_service.name && order.sms_service.name.toLowerCase().includes(searchLower))
                );
            });
            
            // Apply status filter if active
            const finalFiltered = currentSmsOrdersFilter ? 
                filtered.filter(order => order.status === currentSmsOrdersFilter) : 
                filtered;
                
            renderSmsOrders(finalFiltered);
        }

        function filterSmsOrders(status) {
            currentSmsOrdersFilter = status;
            const searchTerm = document.getElementById('smsOrdersSearch').value;
            
            if (!searchTerm) {
                // No search term, just filter by status
                const filtered = status ? 
                    originalSmsOrders.filter(order => order.status === status) : 
                    originalSmsOrders;
                renderSmsOrders(filtered);
            } else {
                // Apply both search and filter
                searchSmsOrders(searchTerm);
            }
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
                                    <th class="px-4 py-2 text-left hidden sm:table-cell">Type</th>
                                    <th class="px-4 py-2 text-left hidden md:table-cell">Network/Service</th>
                                    <th class="px-4 py-2 text-left hidden md:table-cell">Customer</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left hidden sm:table-cell">Status</th>
                                    <th class="px-4 py-2 text-left hidden sm:table-cell">Created</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(o => `
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2">${o.reference}</td>
                                        <td class="px-4 py-2">${o.user ? o.user.name : 'N/A'}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${o.type}</td>
                                    <td class="px-4 py-2 hidden md:table-cell">${o.network || ''}</td>
                                    <td class="px-4 py-2 hidden md:table-cell">${o.phone || ''}</td>
                                        <td class="px-4 py-2">₦${parseFloat(o.amount || 0).toLocaleString()}</td>
                                    <td class="px-4 py-2 hidden sm:table-cell"><span class="status-badge status-completed">completed</span></td>
                                    <td class="px-4 py-2 hidden sm:table-cell">${new Date(o.created_at).toLocaleString()}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load VTU orders</div>'; }
        }

        async function loadPricing() {
            const vtuStatus = document.getElementById('vtuPricingStatus');
            const smsStatus = document.getElementById('smsPricingStatus');
            vtuStatus.textContent = 'Loading...';
            smsStatus.textContent = 'Loading...';
            try {
                const res = await fetch('/api/admin/pricing', { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                
                // VTU Settings
                document.getElementById('markupPercent').value = data.data.markup_percent;
                document.getElementById('currency').value = data.data.currency;
                document.getElementById('autoFx').value = String(data.data.auto_fx);
                
                // SMS Settings
                document.getElementById('smsFxRate').value = data.data.sms_fx_rate || 1600;
                document.getElementById('smsProfitMargin').value = data.data.sms_profit_margin || 15;
                document.getElementById('smsMinPrice').value = data.data.sms_min_price || 1500;
                document.getElementById('smsVat').value = data.data.sms_vat || 700;
                document.getElementById('smsMarkup').value = data.data.sms_markup || 10;
                
                vtuStatus.textContent = '';
                smsStatus.textContent = '';
            } catch (e) { 
                vtuStatus.textContent = 'Failed to load.';
                smsStatus.textContent = 'Failed to load.';
            }
        }

        async function saveVtuPricing(ev) {
            ev.preventDefault();
            const status = document.getElementById('vtuPricingStatus');
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
                status.textContent = data.status === 'success' ? '✅ Saved successfully!' : (data.message || 'Failed to save.');
                setTimeout(() => { status.textContent = ''; }, 3000);
            } catch (e) { status.textContent = '❌ Network error.'; }
            return false;
        }

        async function saveSmsPricing(ev) {
            ev.preventDefault();
            const status = document.getElementById('smsPricingStatus');
            status.textContent = 'Saving...';
            try {
                const payload = {
                    sms_fx_rate: parseFloat(document.getElementById('smsFxRate').value || '1600'),
                    sms_profit_margin: parseFloat(document.getElementById('smsProfitMargin').value || '15'),
                    sms_min_price: parseFloat(document.getElementById('smsMinPrice').value || '1500'),
                    sms_vat: parseFloat(document.getElementById('smsVat').value || '700'),
                    sms_markup: parseFloat(document.getElementById('smsMarkup').value || '10'),
                };
                const res = await fetch('/api/admin/pricing', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    status.textContent = '✅ SMS Pricing updated! Prices will update immediately.';
                    status.className = 'ml-2 text-sm text-green-600';
                } else {
                    status.textContent = data.message || '❌ Failed to save.';
                    status.className = 'ml-2 text-sm text-red-600';
                }
                setTimeout(() => { 
                    status.textContent = ''; 
                    status.className = 'ml-2 text-sm text-slate-500';
                }, 5000);
            } catch (e) { 
                status.textContent = '❌ Network error.';
                status.className = 'ml-2 text-sm text-red-600';
            }
            return false;
        }

        // Price Mapping Display Functions
        async function refreshPriceMapping() {
            try {
                const res = await fetch('/api/admin/pricing', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    updatePriceMappingDisplay(data.data);
                }
            } catch (e) {
                console.error('Error refreshing price mapping:', e);
            }
        }

        function updatePriceMappingDisplay(pricingData) {
            // Update exchange rate
            const fxRate = pricingData.sms_fx_rate || 1600;
            document.getElementById('currentFxRate').textContent = `₦${fxRate.toLocaleString()}`;
            document.getElementById('fxLastUpdated').textContent = `Last updated: ${new Date().toLocaleTimeString()}`;

            // Update profit margin
            const profitMargin = pricingData.sms_profit_margin || 15;
            document.getElementById('currentProfitMargin').textContent = `${profitMargin}%`;

            // Update minimum price
            const minPrice = pricingData.sms_min_price || 1500;
            document.getElementById('currentMinPrice').textContent = `₦${minPrice.toLocaleString()}`;

            // Update base markup
            const markup = pricingData.sms_markup || 10;
            document.getElementById('currentMarkup').textContent = `Base markup: ${markup}%`;

            // Update live calculation example
            updatePriceCalculationExample(pricingData);
        }

        function updatePriceCalculationExample(pricingData) {
            const fxRate = pricingData.sms_fx_rate || 1600;
            const markup = pricingData.sms_markup || 10;
            const vat = pricingData.sms_vat || 700;
            const minPrice = pricingData.sms_min_price || 1500;
            const profitMargin = pricingData.sms_profit_margin || 15;

            // Get provider cost from input field or use default
            const providerCostUsd = parseFloat(document.getElementById('exampleProviderCostInput')?.value || 0.50);
            const convertedNgn = providerCostUsd * fxRate;
            const withMarkup = convertedNgn * (1 + markup / 100);
            const withVat = withMarkup + vat;
            const afterMinPrice = Math.max(withVat, minPrice);
            const finalPrice = afterMinPrice * (1 + profitMargin / 100);

            // Update all calculation steps with current values
            document.getElementById('exampleProviderCost').textContent = `$${providerCostUsd.toFixed(2)}`;
            document.getElementById('exampleConverted').textContent = `₦${Math.round(convertedNgn).toLocaleString()}`;
            document.getElementById('exampleWithMarkup').textContent = `₦${Math.round(withMarkup).toLocaleString()}`;
            document.getElementById('exampleWithVat').textContent = `₦${Math.round(withVat).toLocaleString()}`;
            document.getElementById('exampleMinCheck').textContent = `₦${Math.round(afterMinPrice).toLocaleString()} ${afterMinPrice >= minPrice ? '✓' : '⚠️'}`;
            document.getElementById('exampleWithProfit').textContent = `₦${Math.round(finalPrice).toLocaleString()}`;
            document.getElementById('exampleFinalPrice').textContent = `₦${Math.round(finalPrice).toLocaleString()}`;

            // Update the calculation labels to show current percentages
            document.querySelector('[data-step="markup"]').textContent = `+ Base Markup (${markup}%):`;
            document.querySelector('[data-step="profit"]').textContent = `+ Profit Margin (${profitMargin}%):`;
        }

        function updateExampleProviderCost() {
            // Trigger recalculation when provider cost changes
            const fxRate = parseFloat(document.getElementById('smsFxRate')?.value || 1600);
            const markup = parseFloat(document.getElementById('smsMarkup')?.value || 10);
            const vat = parseFloat(document.getElementById('smsVat')?.value || 700);
            const minPrice = parseFloat(document.getElementById('smsMinPrice')?.value || 1500);
            const profitMargin = parseFloat(document.getElementById('smsProfitMargin')?.value || 15);

            const pricingData = {
                sms_fx_rate: fxRate,
                sms_markup: markup,
                sms_vat: vat,
                sms_min_price: minPrice,
                sms_profit_margin: profitMargin
            };

            updatePriceCalculationExample(pricingData);
        }

        // Load price mapping on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load pricing data when pricing tab is first opened
            const pricingTab = document.querySelector('[data-tab="pricing"]');
            if (pricingTab) {
                pricingTab.addEventListener('click', function() {
                    setTimeout(refreshPriceMapping, 100);
                });
            }

            // Add real-time preview for SMS pricing form
            const smsFormInputs = ['smsFxRate', 'smsProfitMargin', 'smsMinPrice', 'smsVat', 'smsMarkup'];
            smsFormInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', updateLivePreview);
                }
            });

            // Add real-time preview for example provider cost input
            const exampleProviderInput = document.getElementById('exampleProviderCostInput');
            if (exampleProviderInput) {
                exampleProviderInput.addEventListener('input', updateExampleProviderCost);
            }
        });

        function updateLivePreview() {
            const fxRate = parseFloat(document.getElementById('smsFxRate')?.value || 1600);
            const markup = parseFloat(document.getElementById('smsMarkup')?.value || 10);
            const vat = parseFloat(document.getElementById('smsVat')?.value || 700);
            const minPrice = parseFloat(document.getElementById('smsMinPrice')?.value || 1500);
            const profitMargin = parseFloat(document.getElementById('smsProfitMargin')?.value || 15);

            // Update the price mapping display with current form values
            const pricingData = {
                sms_fx_rate: fxRate,
                sms_markup: markup,
                sms_vat: vat,
                sms_min_price: minPrice,
                sms_profit_margin: profitMargin
            };
            updatePriceMappingDisplay(pricingData);
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
                        <td class=\"px-4 py-2 hidden sm:table-cell\">${u.email}</td>
                        <td class=\"px-4 py-2 hidden md:table-cell\">${u.role}</td>
                        <td class=\"px-4 py-2\">₦${parseFloat(u.balance || 0).toLocaleString()}</td>
                        <td class=\"px-4 py-2 hidden sm:table-cell\">${u.status || ''}</td>
                        <td class=\"px-4 py-2 hidden sm:table-cell\">${new Date(u.created_at).toLocaleDateString()}</td>
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
                                    <th class=\"px-4 py-2 text-left hidden sm:table-cell\">Email</th>
                                    <th class=\"px-4 py-2 text-left hidden md:table-cell\">Role</th>
                                    <th class=\"px-4 py-2 text-left\">Balance</th>
                                    <th class=\"px-4 py-2 text-left hidden sm:table-cell\">Status</th>
                                    <th class=\"px-4 py-2 text-left hidden sm:table-cell\">Joined</th>
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
                if (res.status === 'success') { showToast('Balance updated', 'success'); loadUsers(); }
                else { showToast(res.message || 'Failed to update balance', 'error'); }
            }).catch(() => showToast('Network error', 'error'));
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

                const mobileCards = items.map(s => `
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow" data-service-id="${s.id}">
                        <!-- Service Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full ${s.is_active ? 'bg-green-500' : 'bg-red-500'}"></div>
                                <h3 class="font-semibold text-slate-900">${s.name || 'Unnamed Service'}</h3>
                            </div>
                            <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">${s.type}</span>
                        </div>
                        
                        <!-- Service Info -->
                        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                            <div>
                                <span class="text-slate-500">Provider</span>
                                <div class="font-medium text-slate-900">${s.provider || 'N/A'}</div>
                            </div>
                            <div>
                                <span class="text-slate-500">Balance</span>
                                <div class="font-medium text-slate-900">${(s.type === 'SMS' || s.category === 'sms' || !s.type) ? '$' : '₦'}${parseFloat(s.balance || 0).toLocaleString()}</div>
                            </div>
                            <div>
                                <span class="text-slate-500">Price</span>
                                <div class="font-medium text-slate-900">₦${parseFloat((s.price ?? s.rate ?? s.cost ?? 0)).toLocaleString()}</div>
                            </div>
                        </div>
                        
                        <!-- Editable Fields -->
                        <div class="space-y-3">
                            <!-- Service Name -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Service Name</label>
                                <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="name" value="${s.name || ''}" placeholder="Enter service name" />
                            </div>
                            
                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                                <select class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="is_active">
                                    <option value="1" ${s.is_active ? 'selected' : ''}>Active</option>
                                    <option value="0" ${!s.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            
                            <!-- Balance -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Balance ${(s.type === 'SMS' || s.category === 'sms' || !s.type) ? '(USD)' : '(NGN)'}</label>
                                <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="balance" type="number" min="0" step="0.01" value="${s.balance || 0}" />
                            </div>
                            
                            <!-- Priority -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Priority</label>
                                <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="priority" type="number" min="0" value="${s.priority || 0}" />
                            </div>
                            
                            <!-- API URL -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">API URL</label>
                                <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="api_url" value="${s.api_url || ''}" placeholder="Enter API endpoint" />
                            </div>
                            
                            <!-- API Key -->
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">API Key</label>
                                <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono" placeholder="${s.api_key_masked || 'Enter new API key'}" data-field="api_key" />
                            </div>
                            
                            ${s.type==='VTU' ? `
                            <!-- VTU Specific Fields -->
                            <div class="border-t border-slate-200 pt-3 mt-3">
                                <h4 class="text-sm font-medium text-slate-700 mb-3">VTU Credentials</h4>
                                
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Username</label>
                                        <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" data-field="username" value="${s.username || ''}" />
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">Password</label>
                                        <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" type="password" placeholder="${s.password_masked || 'Enter new password'}" data-field="password" />
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1">PIN</label>
                                        <input class="svc-input w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" type="password" placeholder="${s.pin_masked || 'Enter new PIN'}" data-field="pin" />
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            
                            <!-- Save Button -->
                            <div class="pt-3 border-t border-slate-200">
                                <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors" onclick="saveServiceCard(${s.id}, '${s.type}')">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');

                el.innerHTML = `
                    <div class="space-y-4">${mobileCards}</div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load services</div>'; }
        }
        
        let servicesLoadTimeout;
        function debouncedServicesLoad() { clearTimeout(servicesLoadTimeout); servicesLoadTimeout = setTimeout(loadServices, 300); }

        async function loadProviderBalances() {
            const el = document.getElementById('providerBalances');
            el.innerHTML = '<div class="loading">Loading provider balances...</div>';
            
            try {
                const res = await fetch('/api/admin/provider-balances', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const balances = data.data;
                    const totalSmsUsd = (balances.textverified || 0) + (balances.sim5_usd || 0) + (balances.tiger_sms || 0) + (balances.dassy || 0) + (balances.smspool || 0) + (balances.simtoken || 0) + (balances.sms_key || 0) + (balances.sms_wkey || 0);
                    
                    // Create provider cards
                    const providerCards = [];
                    
                    // TextVerified
                    if (balances.textverified > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-blue-900">TextVerified</h3>
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">T</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-blue-900">$${parseFloat(balances.textverified || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-blue-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // 5sim
                    if (balances.sim5_usd > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-green-900">5sim</h3>
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">5</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-green-900">$${parseFloat(balances.sim5_usd || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-green-700">RUB ${parseFloat(balances.sim5_rub || 0).toLocaleString()} → USD</div>
                            </div>
                        `);
                    }
                    
                    // Tiger SMS
                    if (balances.tiger_sms > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-orange-900">Tiger SMS</h3>
                                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">🐅</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-orange-900">$${parseFloat(balances.tiger_sms || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-orange-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // Dassy
                    if (balances.dassy > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-4 border border-pink-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-pink-900">Dassy</h3>
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">D</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-pink-900">$${parseFloat(balances.dassy || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-pink-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // SMSPool
                    if (balances.smspool > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-xl p-4 border border-cyan-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-cyan-900">SMSPool</h3>
                                    <div class="w-8 h-8 bg-cyan-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">S</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-cyan-900">$${parseFloat(balances.smspool || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-cyan-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // SIMTOKEN
                    if (balances.simtoken > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4 border border-indigo-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-indigo-900">SIMTOKEN</h3>
                                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">ST</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-indigo-900">$${parseFloat(balances.simtoken || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-indigo-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // SMS_KEY
                    if (balances.sms_key > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl p-4 border border-teal-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-teal-900">SMS_KEY</h3>
                                    <div class="w-8 h-8 bg-teal-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">SK</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-teal-900">$${parseFloat(balances.sms_key || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-teal-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // SMS_WKEY
                    if (balances.sms_wkey > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-4 border border-amber-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-amber-900">SMS_WKEY</h3>
                                    <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">SW</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-amber-900">$${parseFloat(balances.sms_wkey || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                <div class="text-sm text-amber-700">SMS Provider</div>
                            </div>
                        `);
                    }
                    
                    // VTU.ng
                    if (balances.vtu_ng > 0) {
                        providerCards.push(`
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-purple-900">VTU.ng</h3>
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">V</span>
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-purple-900">₦${parseFloat(balances.vtu_ng || 0).toLocaleString()}</div>
                                <div class="text-sm text-purple-700">VTU Provider</div>
                            </div>
                        `);
                    }
                    
                    el.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            ${providerCards.join('')}
                        </div>
                        
                        <!-- Summary Card -->
                        <div class="mt-6 bg-gradient-to-r from-indigo-50 to-indigo-100 rounded-xl p-6 border border-indigo-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-indigo-900">Total SMS Balance</h3>
                                    <p class="text-sm text-indigo-700">Combined SMS provider balances</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-indigo-900">$${parseFloat(totalSmsUsd).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                    <div class="text-sm text-indigo-700">USD</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Providers (if any) -->
                        ${balances.other_providers && balances.other_providers.length > 0 ? `
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Other Providers</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                ${balances.other_providers.map(provider => `
                                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-slate-900">${provider.name}</span>
                                            <span class="text-sm text-slate-600">${provider.currency} ${parseFloat(provider.balance || 0).toLocaleString()}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Last Updated -->
                        <div class="mt-4 text-center">
                            <div class="text-xs text-slate-500">
                                Last updated: ${new Date().toLocaleString()}
                            </div>
                        </div>
                    `;
                } else {
                    el.innerHTML = '<div class="error">Failed to load provider balances: ' + (data.message || 'Unknown error') + '</div>';
                }
            } catch (e) {
                el.innerHTML = '<div class="error">Error loading provider balances: ' + e.message + '</div>';
            }
        }

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
                
                const mobileCards = rowsData.map(t => `
                    <div class="md:hidden bg-white border border-slate-200 rounded-xl p-4 mb-4 shadow-sm">
                        <!-- Transaction Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full ${t.status === 'success' ? 'bg-green-500' : t.status === 'pending' ? 'bg-yellow-500' : 'bg-red-500'}"></div>
                                <h3 class="font-semibold text-slate-900">${t.type.replace('_', ' ').toUpperCase()}</h3>
                            </div>
                            <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">${t.status}</span>
                        </div>
                        
                        <!-- Transaction Info -->
                        <div class="grid grid-cols-1 gap-3 mb-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">User</span>
                                <span class="font-medium text-slate-900">${t.user ? t.user.name : 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Amount</span>
                                <span class="font-medium text-slate-900">₦${parseFloat(t.amount || 0).toLocaleString()}</span>
                            </div>
                            ${t.description ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Description</span>
                                <span class="font-medium text-slate-900 text-right max-w-48 truncate" title="${t.description}">${t.description}</span>
                            </div>
                            ` : ''}
                            ${t.reference ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Reference</span>
                                <span class="font-mono text-slate-600 text-xs">${t.reference}</span>
                            </div>
                            ` : ''}
                            <div class="flex justify-between">
                                <span class="text-slate-500">Date</span>
                                <span class="font-medium text-slate-900">${new Date(t.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="pt-3 border-t border-slate-200">
                            <div class="flex items-center justify-center">
                                <span class="status-badge status-${t.status}">${t.status.toUpperCase()}</span>
                            </div>
                        </div>
                    </div>
                `).join('');

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
                    <div class="md:hidden space-y-4">${mobileCards}</div>
                    <div class="hidden md:block overflow-x-auto">
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
                    if (res.status === 'success') { showToast('Service updated', 'success'); loadServices(); }
                    else { showToast(res.message || 'Failed to update', 'error'); }
                })
                .catch(() => showToast('Network error', 'error'));
        }
        
        function saveServiceCard(id, type) {
            // Gather card inputs
            const card = document.querySelector(`div[data-service-id="${id}"]`);
            const inputs = card.querySelectorAll('.svc-input');
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
            
            // Show loading state
            const saveBtn = card.querySelector('button[onclick*="saveServiceCard"]');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            
            fetch(url, { method: 'PUT', headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') { 
                        showToast('Service updated successfully', 'success'); 
                        loadServices(); 
                    } else { 
                        showToast(res.message || 'Failed to update service', 'error'); 
                    }
                })
                .catch(() => showToast('Network error', 'error'))
                .finally(() => {
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                });
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
                
                // Mobile cards
                const mobileCards = items.map(r => {
                    const metadata = r.metadata || {};
                    const accountDetails = metadata.account_details || {};
                    const accountNumber = accountDetails.virtual_account_number || metadata.account_number || metadata.account || 'N/A';
                    const bankName = accountDetails.bank_name || metadata.bank_name || metadata.bank || 'N/A';
                    const accountName = accountDetails.account_name || 'N/A';
                    const paymentMethod = metadata.payment_method || metadata.method || 'Bank Transfer';
                    const description = r.description || metadata.description || 'Wallet Deposit';
                    const charges = r.charges || metadata.charges || 0;
                    const actualAmount = r.actual_amount || metadata.actual_amount || r.amount;
                    const creditAmount = r.credit_amount || metadata.credit_amount || r.amount;
                    
                    return `
                    <div class="md:hidden bg-white border border-slate-200 rounded-xl p-4 mb-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="font-semibold text-slate-900">${r.user ? r.user.name : 'N/A'}</div>
                            <span class="status-badge status-${r.status}">${r.status}</span>
                        </div>
                        <div class="space-y-2 text-sm mb-3">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Amount:</span>
                                <span class="font-semibold text-green-600">₦${parseFloat(r.amount || 0).toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Reference:</span>
                                <span class="text-slate-900 font-mono text-xs">${r.reference || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Account Number:</span>
                                <span class="text-slate-900 font-mono text-xs">${accountNumber}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Account Name:</span>
                                <span class="text-slate-900 text-xs">${accountName}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Bank:</span>
                                <span class="text-slate-900 text-xs">${bankName}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Method:</span>
                                <span class="text-slate-900 text-xs">${paymentMethod}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Description:</span>
                                <span class="text-slate-900 text-xs">${description}</span>
                            </div>
                            ${charges > 0 ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Charges:</span>
                                <span class="text-red-600 text-xs">₦${parseFloat(charges).toLocaleString()}</span>
                            </div>
                            ` : ''}
                            ${actualAmount != r.amount ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Actual Amount:</span>
                                <span class="text-blue-600 text-xs">₦${parseFloat(actualAmount).toLocaleString()}</span>
                            </div>
                            ` : ''}
                            ${creditAmount != r.amount ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Credit Amount:</span>
                                <span class="text-green-600 text-xs">₦${parseFloat(creditAmount).toLocaleString()}</span>
                            </div>
                            ` : ''}
                            <div class="flex justify-between">
                                <span class="text-slate-500">Date:</span>
                                <span class="text-slate-900">${new Date(r.created_at).toLocaleDateString()}</span>
                            </div>
                            ${r.updated_at && r.updated_at !== r.created_at ? `
                            <div class="flex justify-between">
                                <span class="text-slate-500">Updated:</span>
                                <span class="text-slate-900">${new Date(r.updated_at).toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                        </div>
                        ${r.status === 'pending' ? `
                            <div class="flex gap-2 pt-3 border-t border-slate-200">
                                <button onclick="openDepositModal(${r.id}, '${r.user ? r.user.name.replace(/'/g, "\\'") : 'N/A'}', '${r.user ? r.user.email : ''}', ${r.amount}, '${r.reference}', 'approve')" 
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors">
                                    ✅ Approve
                                </button>
                                <button onclick="openDepositModal(${r.id}, '${r.user ? r.user.name.replace(/'/g, "\\'") : 'N/A'}', '${r.user ? r.user.email : ''}', ${r.amount}, '${r.reference}', 'deny')" 
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors">
                                    ❌ Deny
                                </button>
                            </div>
                        ` : ''}
                    </div>
                `;
                }).join('');
                
                el.innerHTML = `
                    <div class="md:hidden space-y-4">${mobileCards}</div>
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Reference</th>
                                    <th class="px-4 py-2 text-left hidden lg:table-cell">Account Details</th>
                                    <th class="px-4 py-2 text-left hidden xl:table-cell">Transaction Info</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(r => {
                                    const metadata = r.metadata || {};
                                    const accountDetails = metadata.account_details || {};
                                    const accountNumber = accountDetails.virtual_account_number || metadata.account_number || metadata.account || 'N/A';
                                    const bankName = accountDetails.bank_name || metadata.bank_name || metadata.bank || 'N/A';
                                    const accountName = accountDetails.account_name || 'N/A';
                                    const paymentMethod = metadata.payment_method || metadata.method || 'Bank Transfer';
                                    const description = r.description || metadata.description || 'Wallet Deposit';
                                    const charges = r.charges || metadata.charges || 0;
                                    const actualAmount = r.actual_amount || metadata.actual_amount || r.amount;
                                    const creditAmount = r.credit_amount || metadata.credit_amount || r.amount;
                                    
                                    return `
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-2">
                                            <div class="font-medium">${r.user ? r.user.name : 'N/A'}</div>
                                            <div class="text-xs text-slate-500">${r.user ? r.user.email : ''}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="font-semibold text-green-600">₦${parseFloat(r.amount || 0).toLocaleString()}</div>
                                            ${charges > 0 ? `<div class="text-xs text-red-600">Fee: ₦${parseFloat(charges).toLocaleString()}</div>` : ''}
                                            ${actualAmount != r.amount ? `<div class="text-xs text-blue-600">Actual: ₦${parseFloat(actualAmount).toLocaleString()}</div>` : ''}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="font-mono text-xs">${r.reference || ''}</div>
                                            <div class="text-xs text-slate-500">${description}</div>
                                        </td>
                                        <td class="px-4 py-2 hidden lg:table-cell">
                                            <div class="text-xs">
                                                <div class="font-mono">${accountNumber}</div>
                                                <div class="text-slate-500">${accountName}</div>
                                                <div class="text-slate-500">${bankName}</div>
                                                <div class="text-slate-500">${paymentMethod}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 hidden xl:table-cell">
                                            <div class="text-xs space-y-1">
                                                ${metadata.transaction_type ? `<div><span class="text-slate-500">Type:</span> ${metadata.transaction_type}</div>` : ''}
                                                ${metadata.processed_by ? `<div><span class="text-slate-500">Processed by:</span> ${metadata.processed_by}</div>` : ''}
                                                ${metadata.admin_note ? `<div><span class="text-slate-500">Note:</span> ${metadata.admin_note}</div>` : ''}
                                                ${r.failure_reason ? `<div class="text-red-600"><span class="text-slate-500">Failure:</span> ${r.failure_reason}</div>` : ''}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2"><span class="status-badge status-${r.status}">${r.status}</span></td>
                                        <td class="px-4 py-2">
                                            <div class="text-xs">${new Date(r.created_at).toLocaleString()}</div>
                                            ${r.updated_at && r.updated_at !== r.created_at ? `<div class="text-xs text-slate-500">Updated: ${new Date(r.updated_at).toLocaleString()}</div>` : ''}
                                        </td>
                                        <td class="px-4 py-2">
                                            ${r.status === 'pending' ? `
                                                <button onclick="openDepositModal(${r.id}, '${r.user ? r.user.name.replace(/'/g, "\\'") : 'N/A'}', '${r.user ? r.user.email : ''}', ${r.amount}, '${r.reference}', 'approve')" 
                                                    class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded mr-1">
                                                    ✅ Approve
                                                </button>
                                                <button onclick="openDepositModal(${r.id}, '${r.user ? r.user.name.replace(/'/g, "\\'") : 'N/A'}', '${r.user ? r.user.email : ''}', ${r.amount}, '${r.reference}', 'deny')" 
                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded">
                                                    ❌ Deny
                                                </button>
                                            ` : '<span class="text-slate-400 text-xs">No actions</span>'}
                                        </td>
                                    </tr>
                                `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load deposits</div>'; }
        }
        
        function openDepositModal(id, userName, userEmail, amount, reference, action) {
            window.currentDepositId = id;
            window.currentDepositAction = action;
            
            const title = action === 'approve' ? 'Approve Deposit' : 'Deny Deposit';
            const statusValue = action === 'approve' ? 'completed' : 'failed';
            const statusLabel = action === 'approve' ? '✅ Approve (Credit User)' : '❌ Deny (Payment Failed)';
            const buttonClass = action === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';
            const buttonText = action === 'approve' ? '✅ Approve Deposit' : '❌ Deny Deposit';
            const warning = action === 'approve' ? `<div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4"><p class="text-sm text-yellow-700">⚠️ User balance will be credited with ₦${parseFloat(amount).toLocaleString()}</p></div>` : '';
            
            const modal = document.createElement('div');
            modal.id = 'depositModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">${title}</h3>
                    </div>
                    <div class="px-6 py-4">
                        ${warning}
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Deposit ID:</span>
                                <span class="font-medium">#${id}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">User:</span>
                                <span class="font-medium">${userName}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Email:</span>
                                <span class="font-medium text-xs">${userEmail}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Amount:</span>
                                <span class="font-semibold text-green-600">₦${parseFloat(amount).toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Reference:</span>
                                <span class="font-mono text-xs">${reference}</span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Admin Note (Optional)
                            </label>
                            <textarea id="depositAdminNote" rows="3" 
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                placeholder="Enter reason for ${action === 'approve' ? 'approval' : 'denial'}..."></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 flex gap-3">
                        <button onclick="closeDepositModal()" 
                            class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-4 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button onclick="submitDepositStatus()" 
                            class="flex-1 ${buttonClass} text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            ${buttonText}
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        function closeDepositModal() {
            const modal = document.getElementById('depositModal');
            if (modal) modal.remove();
        }
        
        async function submitDepositStatus() {
            const depositId = window.currentDepositId;
            const action = window.currentDepositAction;
            const note = document.getElementById('depositAdminNote').value;
            const status = action === 'approve' ? 'completed' : 'failed';
            
            try {
                const res = await fetch(`/api/admin/deposits/${depositId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: status,
                        admin_note: note
                    })
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast(action === 'approve' ? '✅ Deposit approved! User balance credited.' : '❌ Deposit denied.', 'success');
                    closeDepositModal();
                    loadDeposits(); // Reload deposits list
                } else {
                    showToast(data.message || 'Failed to update deposit status', 'error');
                }
            } catch (e) {
                showToast('Network error. Please try again.', 'error');
            }
        }

        let depositsLoadTimeout;
        function debouncedDepositsLoad() { clearTimeout(depositsLoadTimeout); depositsLoadTimeout = setTimeout(loadDeposits, 300); }

        async function loadSupportTickets(pageUrl = '/api/support/tickets') {
            const el = document.getElementById('supportTicketsTable');
            el.innerHTML = '<div class="loading">Loading support tickets...</div>';
            const params = new URLSearchParams();
            const status = document.getElementById('supportStatus')?.value || '';
            const priority = document.getElementById('supportPriority')?.value || '';
            const category = document.getElementById('supportCategory')?.value || '';
            if (status) params.append('status', status);
            if (priority) params.append('priority', priority);
            if (category) params.append('category', category);
            const url = params.toString() ? `${pageUrl}?${params}` : pageUrl;
            try {
                const res = await fetch(url, { headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' } });
                const data = await res.json();
                const items = data.data && data.data.data ? data.data.data : [];
                if (!items.length) { el.innerHTML = '<div class="empty-state">No support tickets found</div>'; return; }
                
                // Status badge helper
                const statusBadge = (st) => {
                    const badges = {
                        'open': '🟡 Open',
                        'in_progress': '🔵 In Progress',
                        'resolved': '🟢 Resolved',
                        'closed': '⚫ Closed'
                    };
                    return badges[st] || st;
                };
                
                const priorityBadge = (pr) => {
                    const badges = {
                        'low': 'Low',
                        'medium': 'Medium',
                        'high': '🔶 High',
                        'urgent': '🔴 Urgent'
                    };
                    return badges[pr] || pr;
                };
                
                // Mobile cards
                const mobileCards = items.map(t => `
                    <div class="md:hidden bg-white border border-slate-200 rounded-xl p-4 mb-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="font-semibold text-slate-900">Ticket #${t.id}</div>
                            <span class="status-badge status-${t.status}">${statusBadge(t.status)}</span>
                        </div>
                        <div class="mb-2">
                            <strong class="text-sm">${t.subject}</strong>
                        </div>
                        <div class="space-y-2 text-sm mb-3">
                            <div class="flex justify-between">
                                <span class="text-slate-500">User:</span>
                                <span class="text-slate-900">${t.user ? t.user.name : 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Priority:</span>
                                <span class="font-medium">${priorityBadge(t.priority)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Category:</span>
                                <span class="text-slate-900">${t.category}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Created:</span>
                                <span class="text-slate-900">${new Date(t.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-slate-200">
                            <button onclick="viewTicket(${t.id})" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors">
                                View & Reply
                            </button>
                        </div>
                    </div>
                `).join('');
                
                el.innerHTML = `
                    <div class="md:hidden space-y-4">${mobileCards}</div>
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-50 text-slate-600 text-sm">
                                <tr>
                                    <th class="px-4 py-2 text-left">ID</th>
                                    <th class="px-4 py-2 text-left">Subject</th>
                                    <th class="px-4 py-2 text-left">User</th>
                                    <th class="px-4 py-2 text-left">Category</th>
                                    <th class="px-4 py-2 text-left">Priority</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Created</th>
                                    <th class="px-4 py-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                ${items.map(t => `
                                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                                        <td class="px-4 py-2 font-medium">#${t.id}</td>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-slate-900">${t.subject}</div>
                                            <div class="text-xs text-slate-500">${t.description ? t.description.substring(0, 60) + '...' : ''}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            ${t.user ? t.user.name : 'N/A'}<br>
                                            <small class="text-slate-500">${t.user ? t.user.email : ''}</small>
                                        </td>
                                        <td class="px-4 py-2">${t.category}</td>
                                        <td class="px-4 py-2">${priorityBadge(t.priority)}</td>
                                        <td class="px-4 py-2"><span class="status-badge status-${t.status}">${statusBadge(t.status)}</span></td>
                                        <td class="px-4 py-2">${new Date(t.created_at).toLocaleDateString()}</td>
                                        <td class="px-4 py-2">
                                            <button onclick="viewTicket(${t.id})" 
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded">
                                                View
                                            </button>
                                            ${t.status === 'open' ? `
                                                <button onclick="updateTicketStatus(${t.id}, 'in_progress')" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded ml-1">
                                                    Start
                                                </button>
                                            ` : ''}
                                            ${t.status === 'in_progress' ? `
                                                <button onclick="updateTicketStatus(${t.id}, 'resolved')" 
                                                    class="bg-green-600 hover:bg-green-700 text-white text-xs px-2 py-1 rounded ml-1">
                                                    Resolve
                                                </button>
                                            ` : ''}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) { el.innerHTML = '<div class="error">Failed to load support tickets</div>'; }
        }
        
        function viewTicket(ticketId) {
            // Show loading indicator
            showToast('Loading ticket...', 'info');
            
            fetch(`/api/support/tickets/${ticketId}`, {
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const ticket = data.data.ticket;
                    const messages = ticket.messages || [];
                    
                    // Sort messages by creation date (oldest first for chat-like view)
                    messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    
                    const modal = document.createElement('div');
                    modal.id = 'ticketModal';
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
                    modal.onclick = (e) => { if (e.target === modal) closeTicketModal(); };
                    modal.innerHTML = `
                        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[95vh] flex flex-col">
                            <!-- Sticky Header -->
                            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-xl">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 mr-4">
                                        <h3 class="text-base md:text-lg font-semibold text-slate-900 mb-1">Ticket #${ticket.id}</h3>
                                        <p class="text-sm text-slate-600">${ticket.subject}</p>
                                    </div>
                                    <button onclick="closeTicketModal()" class="text-slate-400 hover:text-slate-600 text-2xl leading-none p-1">×</button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="status-badge status-${ticket.status}">${ticket.status.replace('_', ' ').toUpperCase()}</span>
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full font-medium">${ticket.priority.toUpperCase()}</span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-full">${ticket.category}</span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">
                                    User: ${ticket.user ? ticket.user.name + ' (' + ticket.user.email + ')' : 'N/A'}
                                </div>
                            </div>
                            
                            <!-- Message History (Scrollable) -->
                            <div class="flex-1 px-4 md:px-6 py-4 space-y-3 overflow-y-auto bg-slate-50" style="max-height: 50vh;">
                                <div class="text-xs text-center text-slate-400 mb-4">
                                    🕐 Ticket History - ${messages.length} message${messages.length !== 1 ? 's' : ''}
                                </div>
                                ${messages.length === 0 ? '<div class="text-center text-slate-400 py-8">No messages yet</div>' : ''}
                                ${messages.map((msg, idx) => `
                                    <div class="${msg.is_admin ? 'ml-0 mr-4 md:mr-12' : 'ml-4 md:ml-12 mr-0'}">
                                        <div class="${msg.is_admin ? 'bg-blue-500 text-white' : 'bg-white'} rounded-lg shadow-sm p-3 md:p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold ${msg.is_admin ? 'text-blue-100' : 'text-slate-600'}">
                                                        ${msg.is_admin ? '👨‍💼 Admin' : '👤 User'} ${msg.user ? '· ' + msg.user.name : ''}
                                                    </span>
                                                </div>
                                                <span class="text-xs ${msg.is_admin ? 'text-blue-200' : 'text-slate-400'}">
                                                    ${new Date(msg.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                                </span>
                                            </div>
                                            <div class="text-sm ${msg.is_admin ? 'text-white' : 'text-slate-700'} whitespace-pre-wrap break-words">
                                                ${msg.message}
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            <!-- Reply Section (Sticky Bottom) -->
                            <div class="px-4 md:px-6 py-3 md:py-4 border-t border-slate-200 bg-white rounded-b-xl">
                                ${ticket.status !== 'closed' ? `
                                    <div class="mb-3">
                                        <textarea id="ticketReply" rows="3" 
                                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="Type your reply..."></textarea>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <button id="replyBtn" onclick="replyToTicket(${ticket.id})" 
                                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span class="reply-text">💬 Send Reply</span>
                                            <span class="reply-loading hidden">⏳ Sending...</span>
                                        </button>
                                        ${ticket.status === 'open' ? `
                                            <button id="startBtn" onclick="updateTicketStatusWithLoading(${ticket.id}, 'in_progress', this)" 
                                                class="sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50">
                                                <span class="btn-text">▶️ Start Working</span>
                                                <span class="btn-loading hidden">⏳ Updating...</span>
                                            </button>
                                        ` : ''}
                                        ${ticket.status === 'in_progress' ? `
                                            <button id="resolveBtn" onclick="updateTicketStatusWithLoading(${ticket.id}, 'resolved', this)" 
                                                class="sm:flex-none bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50">
                                                <span class="btn-text">✅ Mark Resolved</span>
                                                <span class="btn-loading hidden">⏳ Updating...</span>
                                            </button>
                                        ` : ''}
                                        ${ticket.status === 'resolved' ? `
                                            <button id="closeBtn" onclick="updateTicketStatusWithLoading(${ticket.id}, 'closed', this)" 
                                                class="sm:flex-none bg-slate-600 hover:bg-slate-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50">
                                                <span class="btn-text">🔒 Close Ticket</span>
                                                <span class="btn-loading hidden">⏳ Closing...</span>
                                            </button>
                                        ` : ''}
                                    </div>
                                ` : '<div class="text-center py-4 text-slate-500">🔒 This ticket is closed</div>'}
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                } else {
                    showToast('Failed to load ticket details', 'error');
                }
            })
            .catch(() => showToast('Network error', 'error'));
        }
        
        function closeTicketModal() {
            const modal = document.getElementById('ticketModal');
            if (modal) modal.remove();
        }
        
        async function replyToTicket(ticketId) {
            const textarea = document.getElementById('ticketReply');
            const replyBtn = document.getElementById('replyBtn');
            const message = textarea.value.trim();
            
            if (!message) {
                showToast('Please enter a message', 'error');
                return;
            }
            
            // Show loading state
            replyBtn.disabled = true;
            replyBtn.querySelector('.reply-text').classList.add('hidden');
            replyBtn.querySelector('.reply-loading').classList.remove('hidden');
            
            try {
                const res = await fetch(`/api/support/tickets/${ticketId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message })
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast('✅ Reply sent successfully!', 'success');
                    closeTicketModal();
                    loadSupportTickets();
                } else {
                    showToast(data.message || 'Failed to send reply', 'error');
                    // Restore button state
                    replyBtn.disabled = false;
                    replyBtn.querySelector('.reply-text').classList.remove('hidden');
                    replyBtn.querySelector('.reply-loading').classList.add('hidden');
                }
            } catch (e) {
                showToast('Network error', 'error');
                // Restore button state
                replyBtn.disabled = false;
                replyBtn.querySelector('.reply-text').classList.remove('hidden');
                replyBtn.querySelector('.reply-loading').classList.add('hidden');
            }
        }
        
        async function updateTicketStatusWithLoading(ticketId, newStatus, btnElement) {
            // Show loading state
            btnElement.disabled = true;
            btnElement.querySelector('.btn-text').classList.add('hidden');
            btnElement.querySelector('.btn-loading').classList.remove('hidden');
            
            try {
                const res = await fetch(`/api/support/tickets/${ticketId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    const statusText = newStatus.replace('_', ' ').toUpperCase();
                    showToast(`✅ Ticket marked as ${statusText}`, 'success');
                    closeTicketModal();
                    loadSupportTickets();
                } else {
                    showToast(data.message || 'Failed to update status', 'error');
                    // Restore button state
                    btnElement.disabled = false;
                    btnElement.querySelector('.btn-text').classList.remove('hidden');
                    btnElement.querySelector('.btn-loading').classList.add('hidden');
                }
            } catch (e) {
                showToast('Network error', 'error');
                // Restore button state
                btnElement.disabled = false;
                btnElement.querySelector('.btn-text').classList.remove('hidden');
                btnElement.querySelector('.btn-loading').classList.add('hidden');
            }
        }
        
        async function updateTicketStatus(ticketId, newStatus) {
            try {
                const res = await fetch(`/api/support/tickets/${ticketId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                
                const data = await res.json();
                
                if (data.status === 'success') {
                    const statusText = newStatus.replace('_', ' ').toUpperCase();
                    showToast(`✅ Ticket marked as ${statusText}`, 'success');
                    closeTicketModal();
                    loadSupportTickets();
                } else {
                    showToast(data.message || 'Failed to update status', 'error');
                }
            } catch (e) {
                showToast('Network error', 'error');
            }
        }

        // V2 Migration Functions
        async function loadV2Migration() {
            loadV2Status();
            loadV2Stats();
            loadV2Logs();
        }

        async function loadV2Status() {
            const statusEl = document.getElementById('v2StatusContent');
            const keyEl = document.getElementById('v2ApiKeyDisplay');
            statusEl.innerHTML = '<div class="text-sm text-slate-500">Loading...</div>';
            
            try {
                const res = await fetch('/api/admin/v2-sync/status', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const d = data.data;
                    statusEl.innerHTML = `
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-slate-600">API Configured</span>
                            <span class="text-sm font-medium ${d.api_configured ? 'text-green-600' : 'text-red-600'}">
                                ${d.api_configured ? '✓ Yes' : '✗ No'}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Synced Users</span>
                            <span class="text-sm font-medium text-slate-900">${d.synced_users_count || 0}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Total Transactions</span>
                            <span class="text-sm font-medium text-slate-900">${d.total_v2_transactions || 0}</span>
                        </div>
                    `;
                    
                    keyEl.textContent = d.api_key || 'Not configured';
                    
                    // Display recent syncs
                    const recentEl = document.getElementById('v2RecentSyncs');
                    if (d.recent_syncs && d.recent_syncs.length) {
                        recentEl.innerHTML = `
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-slate-50 text-slate-600 text-sm">
                                        <tr>
                                            <th class="px-4 py-2 text-left">User ID</th>
                                            <th class="px-4 py-2 text-left">Amount</th>
                                            <th class="px-4 py-2 text-left">Description</th>
                                            <th class="px-4 py-2 text-left">Reference</th>
                                            <th class="px-4 py-2 text-left">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        ${d.recent_syncs.map(s => `
                                            <tr class="border-t border-slate-100">
                                                <td class="px-4 py-2">${s.user_id}</td>
                                                <td class="px-4 py-2">₦${parseFloat(s.amount || 0).toLocaleString()}</td>
                                                <td class="px-4 py-2">${s.description || '-'}</td>
                                                <td class="px-4 py-2 font-mono text-xs">${s.reference || '-'}</td>
                                                <td class="px-4 py-2">${new Date(s.created_at).toLocaleDateString()}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        recentEl.innerHTML = '<div class="empty-state">No recent sync activity</div>';
                    }
                } else {
                    statusEl.innerHTML = '<div class="text-sm text-red-600">Failed to load status</div>';
                }
            } catch (e) {
                statusEl.innerHTML = '<div class="text-sm text-red-600">Error loading status</div>';
            }
        }

        async function loadV2Stats() {
            const statsEl = document.getElementById('v2StatsContent');
            statsEl.innerHTML = '<div class="text-sm text-slate-500">Loading...</div>';
            
            try {
                const res = await fetch('/api/admin/v2-sync/stats', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const s = data.data;
                    statsEl.innerHTML = `
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-slate-600">Users with V2 Activity</span>
                            <span class="text-sm font-medium text-slate-900">${s.total_users_with_v2_activity || 0}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Total V2 Transactions</span>
                            <span class="text-sm font-medium text-slate-900">${s.total_v2_transactions || 0}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Total Debits</span>
                            <span class="text-sm font-medium text-slate-900">₦${parseFloat(s.total_v2_debits || 0).toLocaleString()}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Total Credits</span>
                            <span class="text-sm font-medium text-slate-900">₦${parseFloat(s.total_v2_credits || 0).toLocaleString()}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Syncs Today</span>
                            <span class="text-sm font-medium text-slate-900">${s.v2_syncs_today || 0}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-t border-slate-100">
                            <span class="text-sm text-slate-600">Syncs This Week</span>
                            <span class="text-sm font-medium text-slate-900">${s.v2_syncs_this_week || 0}</span>
                        </div>
                    `;
                } else {
                    statsEl.innerHTML = '<div class="text-sm text-red-600">Failed to load stats</div>';
                }
            } catch (e) {
                statsEl.innerHTML = '<div class="text-sm text-red-600">Error loading stats</div>';
            }
        }

        async function loadV2Logs() {
            const logsEl = document.getElementById('v2LogsContent');
            logsEl.innerHTML = '<div class="loading">Loading migration logs...</div>';
            
            try {
                const res = await fetch('/api/admin/v2-sync/logs', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success' && data.data.migrated_users.length) {
                    logsEl.innerHTML = `
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-slate-50 text-slate-600 text-sm">
                                    <tr>
                                        <th class="px-4 py-2 text-left">User</th>
                                        <th class="px-4 py-2 text-left">Email</th>
                                        <th class="px-4 py-2 text-left">Balance</th>
                                        <th class="px-4 py-2 text-left">V2 Transactions</th>
                                        <th class="px-4 py-2 text-left">Total V2 Amount</th>
                                        <th class="px-4 py-2 text-left">Last Sync</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    ${data.data.migrated_users.map(u => `
                                        <tr class="border-t border-slate-100">
                                            <td class="px-4 py-2">${u.name}</td>
                                            <td class="px-4 py-2">${u.email}</td>
                                            <td class="px-4 py-2">₦${parseFloat(u.balance || 0).toLocaleString()}</td>
                                            <td class="px-4 py-2">${u.v2_transaction_count || 0}</td>
                                            <td class="px-4 py-2">₦${parseFloat(u.total_v2_amount || 0).toLocaleString()}</td>
                                            <td class="px-4 py-2">${new Date(u.last_sync).toLocaleDateString()}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    logsEl.innerHTML = '<div class="empty-state">No migration logs found</div>';
                }
            } catch (e) {
                logsEl.innerHTML = '<div class="error">Error loading migration logs</div>';
            }
        }

        async function regenerateV2ApiKey() {
            if (!confirm('Are you sure you want to regenerate the V2 Sync API key? This will invalidate the current key and V2 site will need to be updated!')) {
                return;
            }
            
            try {
                const res = await fetch('/api/admin/v2-sync/regenerate-key', {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    showToast('API key regenerated successfully!', 'success');
                    document.getElementById('v2ApiKeyDisplay').textContent = data.data.new_api_key;
                    
                    // Show copy button
                    const newKey = data.data.new_api_key;
                    const keyEl = document.getElementById('v2ApiKeyDisplay');
                    keyEl.innerHTML = `
                        ${newKey}
                        <button onclick="copyV2ApiKey('${newKey}')" class="ml-2 text-indigo-600 hover:text-indigo-700 text-xs">
                            Copy
                        </button>
                    `;
                    
                    alert(`New API Key:\n${newKey}\n\nPlease update this key on your V2 site immediately!`);
                } else {
                    showToast(data.message || 'Failed to regenerate API key', 'error');
                }
            } catch (e) {
                showToast('Error regenerating API key', 'error');
            }
        }

        function copyV2ApiKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                showToast('API key copied to clipboard!', 'success');
            }).catch(() => {
                showToast('Failed to copy API key', 'error');
            });
        }




        setTab('overview');
        loadDashboard();
    </script>
    <!-- Toast container and script -->
    <div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 md:left-auto md:right-6 hidden z-50"></div>
    <script>
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const color = type === 'success' ? 'bg-emerald-600' : type === 'error' ? 'bg-rose-600' : 'bg-slate-800';
            toast.innerHTML = `<div class="${color} text-white px-4 py-2 rounded-md shadow transition">${message}</div>`;
            toast.classList.remove('hidden');
            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => { toast.classList.add('hidden'); }, 2500);
        }
    </script>
</body>
</html>
