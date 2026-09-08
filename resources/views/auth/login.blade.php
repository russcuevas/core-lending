<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Core Lending</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    @if(session('success'))
        <meta name="flash-success" content="{{ session('success') }}">
    @endif
    @if(session('error'))
        <meta name="flash-error" content="{{ session('error') }}">
    @endif
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <img src="{{ asset('images/logo.jpg') }}" alt="Core Lending Logo" class="auth-logo-img">
            <h1 class="auth-title">CORE <span>LENDING</span></h1>
            <p class="auth-subtitle">Funding Your Future • Financial Management</p>
        </div>

        <!-- Role Toggle Tabs -->
        <div class="auth-tabs">
            <button type="button" class="auth-tab-btn active" id="tab-staff" onclick="switchAuthTab('staff')">
                Staff / Admin / Host
            </button>
            <button type="button" class="auth-tab-btn" id="tab-client" onclick="switchAuthTab('client')">
                Client Portal
            </button>
        </div>

        <!-- Staff / Admin / Host Form -->
        <form action="{{ route('login.staff') }}" method="POST" id="staff-login-form">
            @csrf
            <div class="form-group">
                <label class="form-label" for="staff-email">Email Address / Username</label>
                <input type="text" name="email" id="staff-email" class="form-control" placeholder="e.g. host@lending.com" required value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="staff-password">Password</label>
                <input type="password" name="password" id="staff-password" class="form-control" placeholder="••••••••" required>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label style="font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Sign In as Staff
            </button>
        </form>

        <!-- Client Login Form -->
        <form action="{{ route('login.client') }}" method="POST" id="client-login-form" style="display: none;">
            @csrf
            <div class="form-group">
                <label class="form-label" for="client-phone">Registered Contact Number (CP No)</label>
                <input type="text" name="phone_number" id="client-phone" class="form-control" placeholder="e.g. 09171234567" required value="{{ old('phone_number') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="client-pin">4-Digit PIN Code</label>
                <input type="password" name="pin_code" id="client-pin" class="form-control" maxlength="4" placeholder="••••" required style="letter-spacing: 4px; font-size: 18px; text-align: center;">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label style="font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="checkbox" name="remember" checked> Remember me
                </label>
                <a href="javascript:void(0)" onclick="openModal('forgotPinModal')" class="auth-link" style="font-size: 12.5px;">Forgot PIN?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Sign In with PIN
            </button>
        </form>

        <!-- One-Click Demo Logins -->
        <div class="demo-credentials-box">
            <div class="demo-credentials-title">
                <span>Quick Demo Logins (1-Click)</span>
            </div>
            <div>
                <span class="demo-pill" onclick="fillDemo('host')">👑 Host Superadmin</span>
                <span class="demo-pill" onclick="fillDemo('encoder')">📝 Admin Encoder</span>
                <span class="demo-pill" onclick="fillDemo('releasing')">💵 Admin Releasing</span>
                <span class="demo-pill" onclick="fillDemo('collector')">🛵 Collector</span>
                <span class="demo-pill" onclick="fillDemo('client')">👤 Client (09171234567)</span>
            </div>
        </div>
    </div>

    <!-- Forgot PIN Modal -->
    <div class="modal-overlay" id="forgotPinModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Reset PIN Code Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('forgotPinModal')">&times;</button>
            </div>
            <form action="{{ route('login.reset_pin') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                        Enter your registered mobile phone number. Our Superadmin will verify and assist in resetting your PIN code.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Contact Number (CP No)</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="09XXXXXXXXX" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address (Optional)</label>
                        <input type="email" name="email" class="form-control" placeholder="juan@example.com">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('forgotPinModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Submit PIN Reset Request</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/common.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
