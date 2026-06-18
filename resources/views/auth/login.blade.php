<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Rekam Medis Bidan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', system_ui, sans-serif;
        }
        
        .login-card {
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .form-input {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .auth-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }

        .password-toggle {
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-4 ring-1 ring-gray-200">
                <i class="bi bi-heart-pulse-fill text-blue-600 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Rekam Medis</h1>
            <p class="text-gray-600 mt-1 text-sm">Sistem Informasi Kesehatan Bidan</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl login-card p-8 ring-1 ring-gray-200">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Masuk ke Akun</h2>
                <p class="text-gray-500 text-sm mt-1">Silakan login menggunakan email dan password Anda</p>
            </div>

            <!-- Error Alert -->
            @if($errors->any())
                <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm flex items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="font-medium">Login Gagal</p>
                        <ul class="mt-1 list-disc list-inside text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm flex items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('status'))
                <div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-gray-400"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            autocomplete="username"
                            class="form-input block w-full pl-11 pr-4 py-3 border border-gray-200 rounded-2xl text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-0"
                            placeholder="admin@gmail.com"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-gray-400"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            class="form-input block w-full pl-11 pr-12 py-3 border border-gray-200 rounded-2xl text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-0"
                            placeholder="••••••••"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="password-toggle absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600"
                            aria-label="Toggle password visibility"
                        >
                            <i class="bi bi-eye text-lg" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>

                    <a href="#" 
                       onclick="alert('Fitur lupa password belum tersedia (dummy). Hubungi administrator.'); return false;"
                       class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
                        Lupa password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 transition-all text-white font-semibold py-3.5 rounded-2xl text-sm shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 focus:outline-none focus:ring-4 focus:ring-blue-500/30"
                >
                    <span>Masuk</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-6 pt-5 border-t border-gray-100">
                <p class="text-center text-xs text-gray-500 mb-2">Akun Demo</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-gray-50 rounded-xl p-2.5 text-center">
                        <div class="font-medium text-gray-700">Admin</div>
                        <div class="text-[10px] text-gray-500 mt-0.5">admin@gmail.com</div>
                        <div class="text-[10px] text-blue-600 font-mono">password</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-2.5 text-center">
                        <div class="font-medium text-gray-700">User / Pegawai</div>
                        <div class="text-[10px] text-gray-500 mt-0.5">user@gmail.com</div>
                        <div class="text-[10px] text-blue-600 font-mono">password</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
            &copy; {{ date('Y') }} Rekam Medis Bidan • Secure Login
        </p>
    </div>

    <script>
        // Tailwind script (already loaded via CDN)
        // Password visibility toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            
            passwordInput.type = isPassword ? 'text' : 'password';
            
            if (isPassword) {
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });

        // Auto focus email on load
        document.getElementById('email').focus();
    </script>
</body>
</html>
