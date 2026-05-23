<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::getSetting('workshop_name', config('app.name', 'Gold Workshop')) }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        html { background-color: #0D0D1A; }
        :root {
            --bg-app: #0D0D1A;
            --bg-sidebar: #111128;
            --bg-card: #161630;
            --bg-surface: #1C1C38;
            --border-color: #252545;

            --gold-primary: #DAA520;
            --gold-bright: #FFD700;
            --gold-deep: #B8860B;
            --gold-pale: rgba(255, 248, 220, 0.1);
            --gold-muted: #C9B880;

            --success: #10B981;
            --error: #F43F5E;
            --warning: #F59E0B;
            --info: #38BDF8;

            --text-primary: #FFFFFF;
            --text-body: #E2E8F0;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;

            --sidebar-width: 260px;
            --header-height: 64px;

            /* Material Design Elevation */
            --shadow-1: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-2: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
            --shadow-3: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
            --shadow-4: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            --shadow-5: 0 19px 38px rgba(0,0,0,0.30), 0 15px 12px rgba(0,0,0,0.22);
        }

        /* Offline Bar */
        #offline-bar {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 40px;
            background-color: #450a0a;
            color: white;
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--error);
        }
        #offline-bar.syncing { background-color: #451a03; border-bottom-color: var(--warning); }
        #offline-bar.synced { background-color: #064e3b; border-bottom-color: var(--success); }

        .sync-progress-container {
            width: 200px;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-left: 10px;
        }
        .sync-progress-bar {
            height: 100%;
            background: var(--gold-primary);
            width: 0%;
            transition: width 0.3s;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-app);
            color: var(--text-body);
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            overflow-x: hidden;
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Material Design Inputs */
        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select,
        textarea {
            background-color: var(--bg-surface);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 10px 14px;
            border-radius: 8px;
            width: 100%;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.15);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.025em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        /* Material Buttons */
        .button, button:not(.logout-btn):not(.header-icon-btn):not(.navbar-toggler):not(.action-btn):not(.toggle-btn):not(.btn-icon):not(.btn-fab) {
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold-primary) 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            box-shadow: var(--shadow-2);
            position: relative;
            overflow: hidden;
        }

        .button:hover, button:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: var(--shadow-3);
        }

        .button:active, button:active {
            transform: translateY(0);
            box-shadow: var(--shadow-1);
        }

        .button-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-body);
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-size: 0.875rem;
        }

        .button-outline:hover {
            border-color: var(--gold-primary);
            color: var(--gold-primary);
            background: rgba(218, 165, 32, 0.05);
        }

        /* Material FAB */
        .btn-fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-deep) 0%, var(--gold-primary) 100%);
            color: white;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-3);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-size: 1.25rem;
        }
        .btn-fab:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-4);
        }
        .btn-fab:active {
            transform: translateY(-1px) scale(0.98);
        }

        /* Material Chips */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 16px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            color: var(--text-body);
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: default;
        }
        .chip.active {
            background: rgba(218, 165, 32, 0.15);
            border-color: var(--gold-primary);
            color: var(--gold-bright);
        }
        .chip-sm { padding: 4px 10px; font-size: 0.75rem; }

        /* Material Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; color: var(--text-body); }
        th {
            text-align: left;
            padding: 14px 16px;
            color: var(--gold-primary);
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 2px solid var(--border-color);
            background: rgba(255,255,255,0.02);
        }
        td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid var(--border-color); transition: background 0.15s ease; }
        tr:hover td { background-color: rgba(255,255,255,0.03); }
        tr:last-child td { border-bottom: none; }

        /* Material Cards */
        .card, .form-section {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-1);
            transition: box-shadow 0.2s ease;
        }
        .card:hover, .form-section:hover {
            box-shadow: var(--shadow-2);
        }

        h1, h2, h3, h4, .font-heading { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .font-urdu { font-family: 'Noto Nastaliq Urdu', serif; }

        /* Layout Structure */
        .app-container { display: flex; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-3);
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            height: var(--header-height);
            background-color: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 999;
            display: flex;
            align-items: center;
            padding: 0 32px;
            box-shadow: var(--shadow-1);
        }

        .content-area {
            margin-top: var(--header-height);
            padding: 32px;
            flex: 1;
        }

        /* Sidebar Branding */
        .sidebar-brand {
            height: 80px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            background: linear-gradient(135deg, #1A1530 0%, #111128 100%);
            border-bottom: 1px solid var(--border-color);
        }

        .brand-icon {
            color: var(--gold-primary);
            font-size: 32px;
            filter: drop-shadow(0 0 8px rgba(218,165,32,0.3));
        }

        .brand-text h1 {
            font-size: 1.15rem;
            color: var(--text-primary);
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .brand-text p {
            font-size: 0.65rem;
            color: var(--gold-muted);
            margin: 0;
            letter-spacing: 0.04em;
        }

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.4), transparent);
            margin: 0 16px;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 0;
        }

        .nav-group-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            padding: 20px 24px 8px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            padding: 10px 20px;
            margin: 2px 12px;
            text-decoration: none;
            color: var(--text-body);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border-radius: 10px;
            border-left: none;
        }

        .nav-item:hover {
            background-color: rgba(218, 165, 32, 0.08);
            color: var(--text-primary);
        }

        .nav-item:hover .nav-icon {
            color: var(--gold-bright);
            filter: drop-shadow(0 0 6px rgba(255, 215, 0, 0.4));
        }

        .nav-item.active {
            background-color: rgba(218, 165, 32, 0.12);
            color: var(--text-primary);
            box-shadow: inset 0 0 12px rgba(218, 165, 32, 0.08);
        }

        .nav-item.active .nav-icon {
            color: var(--gold-bright);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: var(--gold-primary);
            border-radius: 0 3px 3px 0;
        }

        .nav-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-icon {
            font-size: 20px;
            color: var(--gold-muted);
            transition: all 0.2s ease;
        }

        .nav-label-urdu {
            font-size: 0.65rem;
            color: var(--gold-muted);
            margin-left: 34px;
            margin-top: -2px;
            opacity: 0.8;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0,0,0,0.15);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-deep), var(--gold-primary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: var(--shadow-1);
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name {
            color: var(--text-primary);
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: 0.65rem;
            color: var(--gold-primary);
            border: 1px solid rgba(218,165,32,0.3);
            padding: 1px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 2px;
        }

        .logout-btn {
            color: var(--error);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 6px;
            transition: all 0.2s ease;
            border-radius: 8px;
        }
        .logout-btn:hover { background: rgba(244,63,94,0.1); transform: scale(1.1); }

        /* Header Elements */
        .header-title { flex: 1; }
        .header-title h2 { font-size: 1.35rem; color: var(--text-primary); margin: 0; font-weight: 700; }
        .breadcrumb { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }

        .header-search {
            width: 340px;
            position: relative;
            margin: 0 24px;
        }
        .header-search input {
            width: 100%;
            background-color: var(--bg-app);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 9px 14px 9px 40px;
            color: var(--text-body);
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .header-search input:focus {
            outline: none;
            border-color: var(--gold-primary);
            width: 380px;
            box-shadow: 0 0 14px rgba(218, 165, 32, 0.12);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .header-icon-btn:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        .icon-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: var(--error);
            color: white;
            font-size: 0.6rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: var(--shadow-1);
        }
        .icon-badge.warning { background-color: var(--warning); }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: var(--text-secondary);
            background: rgba(255,255,255,0.03);
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            box-shadow: 0 0 8px var(--success);
        }
        .status-dot.offline { background-color: var(--error); box-shadow: 0 0 8px var(--error); }

        /* Ripple effect simulation */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        .ripple::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
            opacity: 0;
            pointer-events: none;
        }
        .ripple:active::after {
            width: 200px; height: 200px;
            opacity: 1;
            transition: 0s;
        }

        /* Page Title */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
        }
        .page-title-group h1 { font-size: 1.9rem; color: var(--text-primary); margin: 0; font-weight: 700; }
        .page-title-group p { font-size: 1.05rem; color: var(--gold-muted); margin: 4px 0 0; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .header { left: 0; padding: 0 16px; }
            .header-search { display: none; }
            .d-mobile { display: flex !important; }
            .content-area { padding: 16px; }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-app); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* Animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up {
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @yield('extra_css')
    </style>
</head>
<body>
    <div id="offline-bar">
        <i class="bi bi-exclamation-circle-fill" id="offline-icon"></i>
        <span id="offline-status-text">You are offline | <span class="font-urdu">آپ آف لائن ہیں</span></span>
        <div class="sync-progress-container" id="sync-progress" style="display: none;">
            <div class="sync-progress-bar" id="sync-progress-bar"></div>
        </div>
        <button class="btn-outline" style="padding: 2px 10px; font-size: 0.7rem; border-color: white; color: white;" onclick="window.location.reload()">Retry Now</button>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <h1>{{ \App\Models\Setting::getSetting('workshop_name', config('app.name', 'Gold Workshop')) }}</h1>
                    <p>Gold Workshop Management | <span class="font-urdu">سونا کارخانہ</span></p>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <nav class="sidebar-nav">
                <div class="nav-group-label">Main</div>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-speedometer2 nav-icon"></i>
                        <span>Dashboard</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">ڈیش بورڈ</span>
                </a>

                <div class="nav-group-label">Transactions</div>
                <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.index', 'invoices.show', 'invoices.edit') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-file-earmark-text nav-icon"></i>
                        <span>Invoices</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">بل</span>
                </a>
                <a href="{{ route('invoices.create') }}" class="nav-item {{ request()->routeIs('invoices.create') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-plus-circle nav-icon"></i>
                        <span>New Invoice</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">نیا بل</span>
                </a>
                <a href="{{ route('gold-receipts.index') }}" class="nav-item {{ request()->routeIs('gold-receipts*') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-box-arrow-in-down nav-icon"></i>
                        <span>Gold Receipts</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">سونا وصولی</span>
                </a>

                <div class="nav-group-label">Parties</div>
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-people nav-icon"></i>
                        <span>Parties</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">گاہک / دوکاندار</span>
                </a>
                <a href="{{ route('ledger.index') }}" class="nav-item {{ request()->routeIs('ledger.*') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-book nav-icon"></i>
                        <span>Ledger</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">کھاتہ</span>
                </a>

                <div class="nav-group-label">Stock</div>
                <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-box-seam nav-icon"></i>
                        <span>Inventory</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">مال</span>
                </a>

                <div class="nav-group-label">Reports</div>
                <a href="{{ route('reports.daily') }}" class="nav-item {{ request()->routeIs('reports.daily') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-bar-chart nav-icon"></i>
                        <span>Daily Report</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">روزانہ</span>
                </a>
                <a href="{{ route('reports.customer') }}" class="nav-item {{ request()->routeIs('reports.customer') ? 'active' : '' }}">
                    <div class="nav-content">
                        <i class="bi bi-person-lines-fill nav-icon"></i>
                        <span>Customer Report</span>
                    </div>
                    <span class="nav-label-urdu font-urdu">گاہک رپورٹ</span>
                </a>

                @if(auth()->user()->getRoleNames()->first() === 'admin')
                    <div class="nav-group-label">System</div>
                    <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <div class="nav-content">
                            <i class="bi bi-gear nav-icon"></i>
                            <span>Settings</span>
                        </div>
                        <span class="nav-label-urdu font-urdu">ترتیبات</span>
                    </a>
                    <a href="{{ route('sync.status') }}" class="nav-item {{ request()->routeIs('sync.status') ? 'active' : '' }}">
                        <div class="nav-content">
                            <i class="bi bi-arrow-repeat nav-icon"></i>
                            <span>Sync Status</span>
                        </div>
                        <span class="nav-label-urdu font-urdu">ہم آہنگی</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="user-avatar">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <i class="bi bi-door-open"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <button class="header-icon-btn d-mobile" id="mobile-toggle" style="margin-right: 15px; display: none;">
                    <i class="bi bi-list"></i>
                </button>
                <div class="header-title">
                    <h2>@yield('title')</h2>
                    <div class="breadcrumb">
                        Gold Workshop / @yield('title')
                    </div>
                </div>

                <div class="header-search">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Search invoices, customers... | تلاش کریں">
                </div>

                <div class="header-actions">
                    <div class="status-indicator">
                        <div class="status-dot" id="online-dot"></div>
                        <span id="online-text">Online</span>
                    </div>

                    <button class="header-icon-btn">
                        <i class="bi bi-bell"></i>
                        <span class="icon-badge" id="failed-sync-badge" style="display: none;">0</span>
                    </button>

                    <button class="header-icon-btn" id="pending-sync-btn" style="display: none;">
                        <i class="bi bi-arrow-repeat warning"></i>
                        <span class="icon-badge warning" id="pending-sync-badge">0</span>
                    </button>
                </div>
            </header>

            <main class="content-area">
                @if(session('success'))
                    <div class="card animate-up" style="border-left: 4px solid var(--success); padding: 16px 22px; margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <i class="bi bi-check-circle-fill" style="color: var(--success); font-size: 1.3rem;"></i>
                            <span style="font-weight: 500;">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="card animate-up" style="border-left: 4px solid var(--error); padding: 16px 22px; margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <i class="bi bi-exclamation-triangle-fill" style="color: var(--error); font-size: 1.3rem;"></i>
                            <span style="font-weight: 500;">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile Toggle
            const mobileToggle = document.getElementById('mobile-toggle');
            const sidebar = document.getElementById('sidebar');
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                if (sidebar.classList.contains('open')) {
                    gsap.fromTo(sidebar, { x: -260 }, { x: 0, duration: 0.4, ease: "power2.out" });
                } else {
                    gsap.to(sidebar, { x: -260, duration: 0.4, ease: "power2.in" });
                }
            });

            // Update online/offline status
            const updateStatus = (state) => {
                const dot = document.getElementById('online-dot');
                const text = document.getElementById('online-text');
                const bar = document.getElementById('offline-bar');

                if (state === 'online') {
                    dot.classList.remove('offline');
                    text.innerText = 'Online';
                    gsap.to(bar, { top: -60, duration: 0.4, ease: "power2.in" });
                } else {
                    dot.classList.add('offline');
                    text.innerText = 'Offline';
                    gsap.to(bar, { top: 0, duration: 0.4, ease: "power2.out" });
                }
            };

            window.addEventListener('online', () => updateStatus('online'));
            window.addEventListener('offline', () => updateStatus('offline'));
            if (!navigator.onLine) updateStatus('offline');

            // Sync Simulation
            document.addEventListener('sync-started', (e) => {
                const bar = document.getElementById('offline-bar');
                const text = document.getElementById('offline-status-text');
                const progress = document.getElementById('sync-progress');

                bar.className = 'syncing';
                bar.style.top = '0px';
                text.innerHTML = 'Syncing items... | <span class="font-urdu">ہم آہنگی ہو رہی ہے</span>';
                progress.style.display = 'block';
            });

            document.addEventListener('sync-progress', (e) => {
                const progressBar = document.getElementById('sync-progress-bar');
                progressBar.style.width = e.detail.percent + '%';
            });

            document.addEventListener('sync-complete', (e) => {
                const bar = document.getElementById('offline-bar');
                const text = document.getElementById('offline-status-text');
                const progress = document.getElementById('sync-progress');

                bar.className = 'synced';
                text.innerHTML = 'Sync Complete! | <span class="font-urdu">ہم آہنگی مکمل ہو گئی</span>';
                progress.style.display = 'none';

                setTimeout(() => {
                    gsap.to(bar, { top: -60, duration: 0.4, ease: "power2.in" });
                }, 4000);
            });

            // Update sync badges
            const updateSyncBadges = async () => {
                if (window.GoldWorkshopDB) {
                    const pending = await window.GoldWorkshopDB.getPendingCount();
                    const pendingBtn = document.getElementById('pending-sync-btn');
                    const pendingBadge = document.getElementById('pending-sync-badge');

                    if (pending > 0) {
                        pendingBtn.style.display = 'flex';
                        pendingBadge.innerText = pending;
                    } else {
                        pendingBtn.style.display = 'none';
                    }
                }
            };

            updateSyncBadges();
        });
    </script>
    @yield('extra_js')
</body>
</html>
