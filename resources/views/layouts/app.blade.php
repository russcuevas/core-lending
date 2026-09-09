<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session('success'))
        <meta name="flash-success" content="{{ session('success') }}">
    @endif
    @if (session('error'))
        <meta name="flash-error" content="{{ session('error') }}">
    @endif
    @if (session('warning'))
        <meta name="flash-warning" content="{{ session('warning') }}">
    @endif

    <title>@yield('title', 'Core Lending') | Funding Your Future</title>

    <!-- Google Fonts & CDN Libraries -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- HTML5 QR Code -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <!-- Simple-DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ versioned_asset('css/common.css') }}">
    @stack('styles')
</head>

<body>
    <div class="app-container">
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeMobileSidebar()"></div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="app-sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('images/logo.jpg') }}" alt="Core Lending" class="brand-logo-img">
                <div style="flex: 1; min-width: 0;">
                    <div class="brand-title">CORE <span>LENDING</span></div>
                    <div class="brand-subtitle">Funding Your Future</div>
                </div>
                <button type="button" class="sidebar-close-btn" onclick="closeMobileSidebar()"
                    aria-label="Close menu">&times;</button>
            </div>

            <nav class="sidebar-nav">
                @php 
                    $role = \Illuminate\Support\Facades\Auth::user()->role ?? ''; 
                @endphp

                @if ($role === 'host')
                    @php
                        $navUnreadApprovals = \App\Models\Loan::where('status', 'pending_host_approval')->where('is_read', false)->count()
                            + \App\Models\WalletTransaction::where('status', 'pending_host_approval')->where('is_read', false)->count()
                            + \App\Models\User::where('role', 'collector')->where('status', 'pending')->where('is_read', false)->count()
                            + \App\Models\ClientUpdateRequest::where('status', 'pending_host_approval')->where('is_read', false)->count();
                    @endphp
                    <div class="nav-label">Main Administration</div>
                    <a href="{{ route('host.dashboard') }}"
                        class="nav-link {{ request()->routeIs('host.dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            </path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('host.approvals.index') }}"
                        class="nav-link {{ request()->routeIs('host.approvals.*') ? 'active' : '' }}" style="display: flex; align-items: center;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Approvals</span>
                        @if($navUnreadApprovals > 0)
                            <span style="margin-left: auto; background: #ef4444; color: #ffffff; font-size: 10px; font-weight: 800; padding: 1.5px 6px; border-radius: 9999px; line-height: 1.2;">{{ $navUnreadApprovals }}</span>
                        @endif
                    </a>
                    <a href="{{ route('host.accounts.index') }}"
                        class="nav-link {{ request()->routeIs('host.accounts.*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <span>Users</span>
                    </a>
                    <a href="{{ route('host.transactions.index') }}"
                        class="nav-link {{ request()->routeIs('host.transactions.*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                        <span>Host Vault & Ledgers</span>
                    </a>
                    <a href="{{ route('host.reports.index') }}"
                        class="nav-link {{ request()->routeIs('host.reports.*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <span>Daily Financial Reports</span>
                    </a>
                    <a href="{{ route('host.settings.index') }}"
                        class="nav-link {{ request()->routeIs('host.settings.*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                        <span>Global Rates & Settings</span>
                    </a>
                @elseif($role === 'admin_encoder')
                    <div class="nav-label">Encoder Station</div>
                    <a href="{{ route('admin.encoder.dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.encoder.dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.encoder.clients.index') }}"
                        class="nav-link {{ request()->routeIs('admin.encoder.clients.index') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <span>Clients</span>
                    </a>
                    <a href="{{ route('admin.encoder.expenses.index') }}"
                        class="nav-link {{ request()->routeIs('admin.encoder.expenses.*') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        <span>Expenses Tracker</span>
                    </a>
                    <a href="{{ route('admin.encoder.collectors.create') }}"
                        class="nav-link {{ request()->routeIs('admin.encoder.collectors.create') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Register Collector</span>
                    </a>
                    <a href="{{ route('admin.encoder.daily_payments') }}"
                        class="nav-link {{ request()->routeIs('admin.encoder.daily_payments') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        <span>Print Daily Payments</span>
                    </a>
                @elseif($role === 'admin_releasing')
                    <div class="nav-label">Disbursement Station</div>
                    <a href="{{ route('admin.releasing.dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.releasing.dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Releasing Request Center</span>
                    </a>
                @elseif($role === 'collector')
                    <div class="nav-label">Field Collections</div>
                    <a href="{{ route('collector.dashboard') }}"
                        class="nav-link {{ request()->routeIs('collector.dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        <span>Collector Portal</span>
                    </a>
                    <a href="{{ route('collector.scan_qr') }}"
                        class="nav-link {{ request()->routeIs('collector.scan_qr') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                            </path>
                        </svg>
                        <span>Scan Client QR</span>
                    </a>
                @elseif($role === 'client')
                    <div class="nav-label">Client Portal</div>
                    <a href="{{ route('client.dashboard') }}"
                        class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                        <span>My Account</span>
                    </a>
                @endif
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <header class="top-navbar">
                <div class="top-navbar-left">
                    <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn"
                        onclick="toggleSidebar()" aria-label="Toggle navigation menu" title="Hide/Show Sidebar">
                        <svg width="20" height="20" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="top-navbar-title">@yield('page_title', 'Core Lending')</div>
                </div>
                <div class="top-navbar-actions">
                    <div class="top-user-profile">
                        <div class="top-user-avatar">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</div>
                        <div class="top-user-info">
                            <div class="top-user-name">{{ auth()->user()->name ?? 'Guest' }}</div>
                            <div class="top-user-role">{{ str_replace('_', ' ', auth()->user()->role ?? '') }}</div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="top-logout-form" style="margin:0; display:inline-flex;">
                            @csrf
                            <button type="submit" class="top-logout-btn" title="Logout">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="content-body">
                <!-- Live Running Date & Time Widget for All Roles (Host, Admin, Collector, Client) -->
                <div class="dashboard-live-clock no-print" style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(9, 30, 58, 0.05); flex-wrap: wrap; gap: 10px;">
                    <div class="live-clock-left" style="display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 600; color: #091e3a;">
                        <span class="live-pulse-dot" style="width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; display: inline-block; flex-shrink: 0;" title="Live System Time"></span>
                        <span id="live_date_display">{{ \Carbon\Carbon::now()->format('l, F d, Y') }}</span>
                    </div>
                    <div class="live-clock-time" id="live_time_display" style="font-family: 'Outfit', -apple-system, sans-serif; font-size: 13.5px; font-weight: 700; color: #0077b6; background: #f0f9ff; border: 1px solid #bae6fd; padding: 4px 12px; border-radius: 6px; letter-spacing: 0.5px; font-variant-numeric: tabular-nums;">
                        {{ \Carbon\Carbon::now()->format('h:i:s A') }}
                    </div>
                </div>

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Simple-DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>

    <!-- Common Scripts -->
    <script src="{{ versioned_asset('js/common.js') }}"></script>
    @stack('scripts')
</body>

</html>
