<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FaddedSMS Admin Login</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center p-4">
	<div class="w-full max-w-md">
		<div class="bg-white/90 backdrop-blur rounded-2xl shadow-xl p-8">
			<div class="text-center mb-6">
				<div class="text-3xl font-bold text-indigo-600">🔆 Fadded VIP</div>
				<div class="text-slate-500">Admin Panel Login</div>
			</div>

			<div id="errorMessage" class="hidden bg-rose-50 text-rose-600 border border-rose-200 rounded-md px-3 py-2 mb-3 text-sm"></div>
			<div id="successMessage" class="hidden bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md px-3 py-2 mb-3 text-sm"></div>

			<form id="loginForm" class="space-y-4">
				<label class="block">
					<span class="text-sm text-slate-600">Email Address</span>
					<input type="email" id="email" name="email" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
				</label>
				<label class="block">
					<span class="text-sm text-slate-600">Password</span>
					<input type="password" id="password" name="password" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
				</label>
				<button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md py-2" id="loginBtn">
					Login to Admin Panel
					<span class="hidden ml-2 align-middle" id="loading">
						<svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
						</svg>
					</span>
				</button>
			</form>

			<div class="text-center mt-4">
				<a href="/" class="text-indigo-600 hover:underline text-sm">← Back to API</a>
			</div>
		</div>
	</div>

	<script>
		document.getElementById('loginForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;
			const loginBtn = document.getElementById('loginBtn');
			const loading = document.getElementById('loading');
			const errorMessage = document.getElementById('errorMessage');
			const successMessage = document.getElementById('successMessage');

			errorMessage.classList.add('hidden');
			successMessage.classList.add('hidden');
			loginBtn.disabled = true; loading.classList.remove('hidden');

			try {
				const response = await fetch('/api/admin/login', {
					method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ email, password })
				});
				const data = await response.json();
				if (data.status === 'success') {
					localStorage.setItem('admin_token', data.data.token);
					localStorage.setItem('admin_user', JSON.stringify(data.data.user));
					successMessage.textContent = 'Login successful! Redirecting...'; successMessage.classList.remove('hidden');
					if (data.data.user.role === 'admin' || data.data.user.role === 'super_admin') {
						setTimeout(() => { window.location.href = '/admin/dashboard'; }, 800);
					} else {
						errorMessage.textContent = 'Access denied. Admin privileges required.'; errorMessage.classList.remove('hidden');
						localStorage.removeItem('admin_token'); localStorage.removeItem('admin_user');
					}
				} else {
					errorMessage.textContent = data.message || 'Login failed'; errorMessage.classList.remove('hidden');
				}
			} catch (error) {
				errorMessage.textContent = 'Network error. Please try again.'; errorMessage.classList.remove('hidden');
			} finally { loginBtn.disabled = false; loading.classList.add('hidden'); }
		});

		window.addEventListener('load', function() {
			const token = localStorage.getItem('admin_token');
			const user = localStorage.getItem('admin_user');
			if (token && user) {
				try {
					const userData = JSON.parse(user);
					if (userData.role === 'admin' || userData.role === 'super_admin') {
						window.location.href = '/admin/dashboard';
					}
				} catch (e) { localStorage.removeItem('admin_token'); localStorage.removeItem('admin_user'); }
			}
		});
	</script>
</body>
</html>
