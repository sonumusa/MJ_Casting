<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Gold Workshop</title>
    <style>
        body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f3f4f6;color:#111}
        .page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .card{width:100%;max-width:420px;background:#fff;border:1px solid #d1d5db;border-radius:16px;box-shadow:0 20px 50px rgba(15,23,42,.08);padding:32px}
        .heading{margin:0 0 24px;font-size:28px;font-weight:700}
        .field{display:flex;flex-direction:column;margin-bottom:16px}
        .field label{margin-bottom:8px;font-size:14px;font-weight:600;color:#334155}
        .field input{border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;font-size:15px}
        .button{width:100%;border:none;border-radius:12px;background:#111827;color:#fff;padding:14px 18px;font-size:15px;font-weight:700;cursor:pointer}
        .hint{font-size:13px;color:#475569;line-height:1.5;margin-top:16px}
        .alert{margin-bottom:16px;padding:14px 16px;border:1px solid #f87171;background:#fef2f2;color:#991b1b;border-radius:12px}
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1 class="heading">Create account</h1>
            @if($errors->any())
                <div class="alert">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ url('register') }}">
                @csrf
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
                <button class="button" type="submit">Create account</button>
            </form>
            <p class="hint">Already have an account? <a href="{{ route('login') }}" style="color:#111827;text-decoration:underline">Log in</a></p>
        </div>
    </div>
</body>
</html>
