<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Gold Workshop</title>
    <style>
        body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f3f4f6;color:#111}
        .page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{width:100%;max-width:420px;background:#fff;border:1px solid #d1d5db;border-radius:16px;box-shadow:0 20px 50px rgba(15,23,42,.08);padding:32px}
        .heading{margin:0 0 24px;font-size:28px;font-weight:700}
        .field{display:flex;flex-direction:column;margin-bottom:16px}
        .field label{margin-bottom:8px;font-size:14px;font-weight:600;color:#334155}
        .field input{border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;font-size:15px}
        .actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
        .actions a{color:#111827;text-decoration:none;font-size:14px}
        .button{width:100%;border:none;border-radius:12px;background:#111827;color:#fff;padding:14px 18px;font-size:15px;font-weight:700;cursor:pointer}
        .hint{font-size:13px;color:#475569;line-height:1.5;margin-top:16px}
        .alert{margin-bottom:16px;padding:14px 16px;border:1px solid #f87171;background:#fef2f2;color:#991b1b;border-radius:12px}
        .checkbox{display:inline-flex;align-items:center;gap:8px;font-size:14px;color:#334155}
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1 class="heading">Sign in</h1>
            @if($errors->any())
                <div class="alert">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ url('login') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="actions">
                    <label class="checkbox"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="{{ route('register') }}">Create account</a>
                </div>
                <button class="button" type="submit">Log in</button>
            </form>
            <p class="hint">Admin: admin@goldworkshop.test / admin123<br>User: user@goldworkshop.test / user12345</p>
        </div>
    </div>
</body>
</html>
