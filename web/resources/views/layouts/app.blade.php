<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kişisel Finans Takip') - {{ config('app.name', 'Finance Tracker') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; background: #f5f5f5; color: #333; }
        .container { max-width: 900px; margin: 0 auto; padding: 1rem; }
        nav { background: #1e3a5f; color: #fff; padding: 0.75rem 1rem; margin-bottom: 1.5rem; }
        nav .container { display: flex; justify-content: space-between; align-items: center; }
        nav a { color: #fff; text-decoration: none; margin-right: 1rem; }
        nav a:hover { text-decoration: underline; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; font-size: 1rem; }
        .btn-primary { background: #1e3a5f; color: #fff; }
        .btn-primary:hover { background: #2a4a7a; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.25rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .text-danger { color: #c00; font-size: 0.875rem; margin-top: 0.25rem; }
        .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .text-right { text-align: right; }
        .monthly-summary { font-size: 1.25rem; margin: 1rem 0; }
    </style>
    @stack('styles')
</head>
<body>
    @if(session('user_id'))
    <nav>
        <div class="container">
            <div>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('expenses.index') }}">Harcamalar</a>
                <a href="{{ route('expenses.create') }}">Harcama Ekle</a>
            </div>
            <div>
                <span>{{ session('user_name') }}</span>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Çıkış</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
            </div>
        </div>
    </nav>
    @endif
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
            </div>
        @endif
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
