<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Management - FaddedSMS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 md:relative md:translate-x-0 transform -translate-x-full transition-transform duration-300 ease-in-out" id="sidebar">
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-200">
                <div class="flex items-center space-x-3">
                    <div class="text-2xl font-bold text-indigo-600">🔆 Fadded VIP</div>
                </div>
                <button class="md:hidden p-2 rounded-md hover:bg-slate-100" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
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
                <a href="/admin/dashboard#deposits" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💰</span>
                    <span>Deposits</span>
                </a>
                <a href="/admin/dashboard#transactions" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>📋</span>
                    <span>Transactions</span>
                </a>
                <a href="/admin/dashboard#support" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>💬</span>
                    <span>Support Tickets</span>
                </a>
                <a href="/admin/dashboard#v2migration" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>🔄</span>
                    <span>V2 Migration</span>
                </a>
                <a href="/admin/dashboard#pricing" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>⚙️</span>
                    <span>Pricing</span>
                </a>
                <a href="/admin/dashboard#apiservices" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
                    <span>🔧</span>
                    <span>API</span>
                </a>
                <a href="/admin/advertisements" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md bg-slate-100 text-indigo-700 font-semibold">
                    <span>📢</span>
                    <span>Advertisements</span>
                </a>
                <a href="/admin/broadcasts" class="nav-btn flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-slate-100 text-slate-700">
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
                        <h1 class="text-2xl font-bold text-slate-900">Advertisement Management</h1>
                        <p class="text-slate-600">Manage advertisements displayed on the frontend</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600" id="userName">Loading...</span>
                        <button onclick="logout()" class="bg-rose-500 hover:bg-rose-600 text-white text-sm px-3 py-1.5 rounded-md">Logout</button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <!-- Action Buttons -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <button onclick="loadAdvertisements()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            🔄 Refresh
                        </button>
                    </div>
                    <button onclick="showAdForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        ➕ Add Advertisement
                    </button>
                </div>

                <!-- Advertisement Form (Hidden by default) -->
                <div id="adFormCard" class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6" style="display: none;">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold" id="adFormTitle">Add Advertisement</h3>
                        <button onclick="hideAdForm()" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <form id="adForm" onsubmit="saveAdvertisement(event)">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
                                    <input type="text" id="adTitle" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Enter advertisement title">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                                    <textarea id="adDescription" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" rows="3" placeholder="Enter advertisement description"></textarea>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Button Text</label>
                                        <input type="text" id="adButtonText" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="e.g., Learn More">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Button URL</label>
                                        <input type="url" id="adButtonUrl" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="https://example.com">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Background Type</label>
                                    <select id="adBackgroundType" onchange="toggleBackgroundOptions()" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                                        <option value="color">Color</option>
                                        <option value="image">Image</option>
                                    </select>
                                </div>
                                
                                <div id="backgroundColorSection">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Background Color</label>
                                    <input type="color" id="adBackgroundColor" value="#3B82F6" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                                </div>
                                
                                <div id="backgroundImageSection" style="display:none;">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Background Image</label>
                                    <input type="file" id="adBackgroundImage" accept="image/*" onchange="previewBackgroundImage(event)" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                                    <div id="backgroundImagePreview" class="mt-2" style="display:none;">
                                        <img id="backgroundImagePreviewImg" src="" alt="Preview" class="max-w-full h-32 rounded-md border border-slate-300">
                                        <button type="button" onclick="clearBackgroundImage()" class="mt-2 text-sm text-red-600 hover:text-red-700">Remove Image</button>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Recommended size: 800x400px (2:1 ratio)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Text Color</label>
                                    <input type="color" id="adTextColor" value="#FFFFFF" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order</label>
                                        <input type="number" id="adSortOrder" min="0" value="0" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                                    </div>
                                    <div class="flex items-center space-x-4 pt-6">
                                        <label class="flex items-center">
                                            <input type="checkbox" id="adIsActive" checked class="rounded border-slate-300 text-blue-600">
                                            <span class="ml-2 text-sm text-slate-700">Active</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" id="adIsFeatured" class="rounded border-slate-300 text-blue-600">
                                            <span class="ml-2 text-sm text-slate-700">Featured</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 mt-6">
                                <button type="button" onclick="hideAdForm()" class="px-4 py-2 border border-slate-300 rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                                    Save Advertisement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Advertisements Table -->
                <div id="adListCard" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div id="advertisementsTable" class="p-6">
                        <div class="text-sm text-slate-500 text-center py-8">Loading advertisements...</div>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <!-- Toast container -->
    <div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 md:left-auto md:right-6 hidden z-50"></div>

    <script>
        let adminToken = localStorage.getItem('admin_token');
        let adminUser = JSON.parse(localStorage.getItem('admin_user') || '{}');
        let currentAdId = null;
        
        // Check authentication
        if (!adminToken || !adminUser.role || !['admin', 'super_admin'].includes(adminUser.role)) {
            window.location.href = '/admin/login';
        }
        
        document.getElementById('userName').textContent = adminUser.name || 'Admin';
        
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
        
        // Logout function
        function logout() {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_user');
            window.location.href = '/admin/login';
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const color = type === 'success' ? 'bg-emerald-600' : type === 'error' ? 'bg-rose-600' : 'bg-slate-800';
            toast.innerHTML = `<div class="${color} text-white px-4 py-2 rounded-md shadow transition">${message}</div>`;
            toast.classList.remove('hidden');
            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => { toast.classList.add('hidden'); }, 2500);
        }

        // Advertisement Management Functions
        async function loadAdvertisements() {
            const tableEl = document.getElementById('advertisementsTable');
            tableEl.innerHTML = '<div class="text-sm text-slate-500 text-center py-8">Loading advertisements...</div>';
            
            try {
                const response = await fetch('/api/admin/advertisements', {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.success) {
                    const ads = data.data;
                    if (ads.length === 0) {
                        tableEl.innerHTML = '<div class="text-sm text-slate-500 text-center py-8">No advertisements found</div>';
                        return;
                    }
                    
                    let html = '<div class="grid gap-4">';
                    ads.forEach(ad => {
                        const hasImage = ad.background_type === 'image' && ad.background_image;
                        html += `
                            <div class="border border-slate-200 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    ${hasImage ? `
                                        <div class="flex-shrink-0">
                                            <img src="${ad.background_image}" alt="${ad.title}" class="w-24 h-24 object-cover rounded-md border border-slate-300">
                                        </div>
                                    ` : ''}
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="font-medium text-slate-900">${ad.title}</h3>
                                            ${ad.is_featured ? '<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">Featured</span>' : ''}
                                            ${ad.is_active ? '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active</span>' : '<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Inactive</span>'}
                                        </div>
                                        <p class="text-sm text-slate-600 mb-2">${ad.description || 'No description'}</p>
                                        <div class="text-xs text-slate-500 mb-1">
                                            Button: "${ad.button_text}" → ${ad.button_url}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Background: ${ad.background_type === 'color' ? `<span class="inline-block w-4 h-4 rounded border border-slate-300" style="background-color: ${ad.background_color}"></span> ${ad.background_color}` : '📷 Image'}
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <button onclick="editAdvertisement(${ad.id})" class="text-blue-600 hover:text-blue-700 text-sm whitespace-nowrap">✏️ Edit</button>
                                        <button onclick="toggleAdStatus(${ad.id})" class="text-orange-600 hover:text-orange-700 text-sm whitespace-nowrap">${ad.is_active ? '⏸️ Deactivate' : '▶️ Activate'}</button>
                                        <button onclick="deleteAdvertisement(${ad.id})" class="text-red-600 hover:text-red-700 text-sm whitespace-nowrap">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    tableEl.innerHTML = html;
                } else {
                    tableEl.innerHTML = `<div class="text-sm text-red-600 text-center py-8">Failed to load advertisements: ${data.message || 'Unknown error'}</div>`;
                }
            } catch (e) {
                tableEl.innerHTML = `<div class="text-sm text-red-600 text-center py-8">Error loading advertisements: ${e.message}</div>`;
            }
        }

        function showAdForm(adId = null) {
            console.log('showAdForm called with adId:', adId);
            currentAdId = adId;
            const formCard = document.getElementById('adFormCard');
            const listCard = document.getElementById('adListCard');
            const title = document.getElementById('adFormTitle');
            
            if (!formCard) {
                console.error('Form card element not found!');
                showToast('Form element not found', 'error');
                return;
            }
            
            if (adId) {
                title.textContent = 'Edit Advertisement';
                loadAdData(adId);
            } else {
                title.textContent = 'Add Advertisement';
                resetAdForm();
            }
            
            // Show form card and hide list card
            formCard.style.display = 'block';
            listCard.style.display = 'none';
            
            // Scroll to top of form
            formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function hideAdForm() {
            const formCard = document.getElementById('adFormCard');
            const listCard = document.getElementById('adListCard');
            
            formCard.style.display = 'none';
            listCard.style.display = 'block';
            currentAdId = null;
        }

        function resetAdForm() {
            const form = document.getElementById('adForm');
            form.reset();
            document.getElementById('adBackgroundColor').value = '#3B82F6';
            document.getElementById('adTextColor').value = '#FFFFFF';
            document.getElementById('adSortOrder').value = '0';
            document.getElementById('adIsActive').checked = true;
            document.getElementById('adIsFeatured').checked = false;
            document.getElementById('adBackgroundType').value = 'color';
            toggleBackgroundOptions();
            clearBackgroundImage();
        }

        function toggleBackgroundOptions() {
            const type = document.getElementById('adBackgroundType').value;
            const colorSection = document.getElementById('backgroundColorSection');
            const imageSection = document.getElementById('backgroundImageSection');
            
            if (type === 'color') {
                colorSection.style.display = 'block';
                imageSection.style.display = 'none';
            } else {
                colorSection.style.display = 'none';
                imageSection.style.display = 'block';
            }
        }

        function previewBackgroundImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('backgroundImagePreview');
            const previewImg = document.getElementById('backgroundImagePreviewImg');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function clearBackgroundImage() {
            const fileInput = document.getElementById('adBackgroundImage');
            const preview = document.getElementById('backgroundImagePreview');
            const previewImg = document.getElementById('backgroundImagePreviewImg');
            
            fileInput.value = '';
            previewImg.src = '';
            preview.style.display = 'none';
        }

        async function loadAdData(adId) {
            try {
                const response = await fetch(`/api/admin/advertisements`, {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.success) {
                    const ad = data.data.find(a => a.id === adId);
                    if (ad) {
                        document.getElementById('adTitle').value = ad.title;
                        document.getElementById('adDescription').value = ad.description || '';
                        document.getElementById('adButtonText').value = ad.button_text;
                        document.getElementById('adButtonUrl').value = ad.button_url;
                        document.getElementById('adBackgroundType').value = ad.background_type;
                        document.getElementById('adBackgroundColor').value = ad.background_color;
                        document.getElementById('adTextColor').value = ad.text_color;
                        document.getElementById('adSortOrder').value = ad.sort_order;
                        document.getElementById('adIsActive').checked = ad.is_active;
                        document.getElementById('adIsFeatured').checked = ad.is_featured;
                        toggleBackgroundOptions();
                        
                        // Show existing background image if present
                        if (ad.background_type === 'image' && ad.background_image) {
                            const preview = document.getElementById('backgroundImagePreview');
                            const previewImg = document.getElementById('backgroundImagePreviewImg');
                            previewImg.src = ad.background_image;
                            preview.style.display = 'block';
                        }
                    } else {
                        showToast('Advertisement not found', 'error');
                    }
                } else {
                    showToast('Failed to load advertisement data', 'error');
                }
            } catch (e) {
                showToast('Error loading advertisement data', 'error');
            }
        }

        async function saveAdvertisement(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('title', document.getElementById('adTitle').value);
            formData.append('description', document.getElementById('adDescription').value);
            formData.append('button_text', document.getElementById('adButtonText').value);
            formData.append('button_url', document.getElementById('adButtonUrl').value);
            formData.append('background_type', document.getElementById('adBackgroundType').value);
            formData.append('background_color', document.getElementById('adBackgroundColor').value);
            formData.append('text_color', document.getElementById('adTextColor').value);
            formData.append('sort_order', document.getElementById('adSortOrder').value);
            formData.append('is_active', document.getElementById('adIsActive').checked ? '1' : '0');
            formData.append('is_featured', document.getElementById('adIsFeatured').checked ? '1' : '0');
            
            const imageFile = document.getElementById('adBackgroundImage').files[0];
            if (imageFile) {
                formData.append('background_image', imageFile);
            }
            
            try {
                const url = currentAdId ? `/api/admin/advertisements/${currentAdId}` : '/api/admin/advertisements';
                
                // For updates, add _method field for Laravel to recognize PUT request
                if (currentAdId) {
                    formData.append('_method', 'PUT');
                }
                
                const response = await fetch(url, {
                    method: 'POST', // Always use POST with FormData
                    headers: { 'Authorization': `Bearer ${adminToken}` },
                    body: formData
                });
                
                const data = await response.json();
                console.log('Save response:', data);
                
                if (data.success) {
                    showToast(data.message || 'Advertisement saved successfully', 'success');
                    hideAdForm();
                    loadAdvertisements();
                } else {
                    showToast(data.message || 'Failed to save advertisement', 'error');
                }
            } catch (e) {
                showToast('Error saving advertisement', 'error');
            }
        }

        async function editAdvertisement(adId) {
            showAdForm(adId);
        }

        async function toggleAdStatus(adId) {
            if (!confirm('Are you sure you want to toggle this advertisement status?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/admin/advertisements/${adId}/toggle`, {
                    method: 'PATCH',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Advertisement status updated', 'success');
                    loadAdvertisements();
                } else {
                    showToast(data.message || 'Failed to update advertisement status', 'error');
                }
            } catch (e) {
                showToast('Error updating advertisement status', 'error');
            }
        }

        async function deleteAdvertisement(adId) {
            if (!confirm('Are you sure you want to delete this advertisement? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/admin/advertisements/${adId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Advertisement deleted successfully', 'success');
                    loadAdvertisements();
                } else {
                    showToast(data.message || 'Failed to delete advertisement', 'error');
                }
            } catch (e) {
                showToast('Error deleting advertisement', 'error');
            }
        }

        // Load advertisements on page load
        loadAdvertisements();
    </script>
</body>
</html>
