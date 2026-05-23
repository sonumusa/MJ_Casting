<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Gold Workshop</title>
    <style>
        body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f3f4f6;color:#111}
        .page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{width:100%;max-width:760px;background:#fff;border:1px solid #d1d5db;border-radius:20px;box-shadow:0 20px 50px rgba(15,23,42,.08);padding:32px}
        .heading{margin:0 0 24px;font-size:32px;font-weight:700}
        .section{margin-bottom:24px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
        .stat{padding:20px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc}
        .stat-label{margin:0;font-size:13px;color:#475569}
        .stat-value{margin:8px 0 0;font-size:18px;font-weight:700;color:#0f172a}
        .actions{display:flex;gap:12px;flex-wrap:wrap}
        .button{display:inline-flex;align-items:center;justify-content:center;border:none;border-radius:12px;background:#111827;color:#fff;padding:14px 20px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none}
        .link-card{display:block;padding:18px;border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;color:#0f172a;text-decoration:none}
        .link-card strong{display:block;margin-bottom:8px;font-size:16px}
        .hint{font-size:14px;color:#475569;line-height:1.8}
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1 class="heading">Dashboard</h1>
            <p class="hint">Welcome, {{ auth()->user()->name }}.</p>

            <div class="grid">
                <div class="stat">
                    <p class="stat-label">Role</p>
                    <p class="stat-value">{{ auth()->user()->getRoleNames()->first() ?? 'user' }}</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Email</p>
                    <p class="stat-value">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <div class="section">
                <p class="hint">Use these links to navigate the core app sections. The API is also available under <code>/api/v1</code>.</p>
                <div class="grid">
                    <a href="{{ url('dashboard') }}" class="link-card"><strong>Dashboard</strong>View the application home screen.</a>
                    <a href="{{ url('/login') }}" class="link-card"><strong>Login page</strong>Return to the login screen.</a>
                    <a href="{{ url('/register') }}" class="link-card"><strong>Register</strong>Create a new user account.</a>
                    <a href="{{ url('/api/v1/invoices') }}" class="link-card"><strong>Invoices API</strong>View invoices API endpoint (requires auth token).</a>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;">
                @csrf
                <button type="submit" class="button">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>
