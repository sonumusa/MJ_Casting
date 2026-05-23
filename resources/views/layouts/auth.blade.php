<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Gold Workshop') }} - @yield('title')</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Inter, system-ui, sans-serif; background: linear-gradient(135deg, #020617 0%, #111827 100%); color: #e2e8f0; }
        .card { width: min(420px, calc(100% - 32px)); background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 32px; box-shadow: 0 20px 55px rgba(15, 23, 42, 0.35); }
        h1 { margin: 0 0 24px; font-size: 1.75rem; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; color: #cbd5e1; }
        input { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; }
        button { width: 100%; padding: 13px 18px; border: none; border-radius: 12px; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
        .meta { margin-top: 16px; font-size: 0.95rem; color: #94a3b8; }
        .meta a { color: #60a5fa; }
        .error { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #1f2937; border: 1px solid #991b1b; color: #fecaca; }
    </style>
</head>
<body>
<div class="card">
    <h1>@yield('title')</h1>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>
