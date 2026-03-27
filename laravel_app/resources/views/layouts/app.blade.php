<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Personal Finance Tracker') - {{ config('app.name', 'Finance Tracker') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
        :root {
            --bg: #eef3ff;
            --bg2: #e4ecff;
            --surface: #ffffff;
            --surface2: #f4f7ff;
            --border: rgba(79, 102, 255, 0.14);
            --border2: rgba(79, 102, 255, 0.24);
            --acc: #2859f6;
            --acc2: #4f7dff;
            --acc-light: #eaf0ff;
            --acc-glow: rgba(40, 89, 246, 0.22);
            --green: #10b981;
            --green-light: #ecfdf5;
            --amber: #f59e0b;
            --amber-light: #fffbeb;
            --red: #ef4444;
            --red-light: #fef2f2;
            --txt: #0f172a;
            --txt2: #475569;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
            margin: 0;
            color: var(--txt);
            min-height: 100vh;
            background:
                radial-gradient(820px 380px at 82% -4%, rgba(40,89,246,0.18) 0%, rgba(40,89,246,0.04) 42%, transparent 74%),
                radial-gradient(660px 320px at 10% 4%, rgba(79,125,255,0.16) 0%, rgba(79,125,255,0.03) 52%, transparent 76%),
                linear-gradient(180deg, #f8faff 0%, #edf3ff 44%, #e9f0ff 100%);
        }
        .mono, .currency-value, .number-value, .date-cell, .amount-cell {
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .app-shell, .main { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            background: rgba(255,255,255,0.80);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 56px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 34px rgba(40,89,246,0.12);
        }
        .topbar-left { display:flex; align-items:center; gap: 12px; }
        .topbar-right { display:flex; align-items:center; gap: 10px; }
        .sidebar-header { display:flex; align-items:center; gap:8px; }
        .sidebar-logo-circle {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color:#fff; display:inline-flex; align-items:center; justify-content:center; font-weight:700;
        }
        .sidebar-title { font-weight: 700; color: var(--txt); }
        .topbar-nav {
            display:flex;
            align-items:center;
            gap: 10px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.88);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 4px 6px;
            box-shadow: 0 8px 24px rgba(40,89,246,0.14);
        }
        .sidebar-link {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            transition: all 0.22s ease;
        }
        .sidebar-link:hover {
            color: var(--acc);
            background: var(--acc-light);
            transform: translateY(-1px);
        }
        .sidebar-link::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 4px;
            width: 0;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--acc), var(--acc2));
            transform: translateX(-50%);
            transition: width 0.22s ease;
        }
        .sidebar-link:hover::after { width: 58%; }
        .sidebar-link--active {
            color: #fff;
            background: linear-gradient(90deg, var(--acc), var(--acc2));
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(40,89,246,0.22);
        }
        .sidebar-link--active::after { display: none; }
        .sidebar-user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: #fff; display:inline-flex; align-items:center; justify-content:center; font-weight:700;
        }
        .sidebar-footer { position: relative; }
        .sidebar-user-trigger { cursor:pointer; }
        .sidebar-user-dropdown {
            position:absolute; right:0; top:2.6rem; min-width: 200px;
            background: var(--surface); border:1px solid var(--border2); border-radius: 12px;
            box-shadow: 0 14px 30px rgba(40,89,246,0.18);
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        }
        .sidebar-user-dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .sidebar-user-dropdown-link {
            display:block; width:100%; text-align:left; border:0; background:transparent; cursor:pointer;
            padding: 8px 10px; border-radius: 8px; color: var(--txt2); text-decoration:none; font-family: inherit;
        }
        .sidebar-user-dropdown-link:hover { background: var(--surface2); color: var(--txt); }

        .main-inner {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px;
        }
        h1 { margin: 0 0 14px; font-size: 24px; font-weight: 600; color: var(--txt); }
        p { font-size: 14px; color: var(--txt2); }
        .section-label {
            font-size: 11px; font-weight: 600; color: var(--muted);
            letter-spacing: 0.08em; text-transform: uppercase;
        }
        .card {
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 14px rgba(0,0,0,0.05), 0 16px 36px rgba(40,89,246,0.10), 0 1px 0 rgba(255,255,255,0.7) inset;
            padding: 20px 24px;
            margin-bottom: 1.25rem;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.07), 0 24px 52px rgba(40,89,246,0.16), 0 4px 16px rgba(0,0,0,0.06);
        }
        .card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 44px;
            background: linear-gradient(180deg, rgba(59,130,246,0.06), rgba(59,130,246,0));
            pointer-events: none;
        }
        .btn {
            border-radius: 10px;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--acc), var(--acc2));
            color: #fff;
            box-shadow: 0 8px 18px rgba(40,89,246,0.30);
        }
        .btn-primary:hover {
            box-shadow: 0 10px 24px rgba(40,89,246,0.38);
            transform: translateY(-1px);
            filter: saturate(1.08);
        }
        .btn-primary:active { transform: scale(0.97); }
        .btn-secondary {
            background: #fff;
            border: 1px solid var(--border2);
            color: var(--txt2);
            font-weight: 500;
        }
        .btn-secondary:hover { background: var(--surface2); border-color: var(--acc); color: var(--txt); }

        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--txt2);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: var(--surface);
            border: 1.5px solid var(--border2);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            color: var(--txt);
            width: 100%;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--acc);
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
            outline: none;
        }
        .form-group textarea { min-height: 90px; resize: vertical; }

        .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.92rem; }
        .alert-success { background: var(--green-light); color: #065f46; border: 1px solid rgba(16,185,129,0.3); }
        .alert-error { background: var(--red-light); color: #b91c1c; border: 1px solid rgba(239,68,68,0.3); }
        .text-danger { color: var(--red); font-size: 0.85rem; margin-top: 0.25rem; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); }
        th {
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: var(--surface2);
        }
        td { font-size: 13px; color: var(--txt); font-weight: 400; }
        .text-right { text-align: right; }

        .badge-category {
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
        }
        .badge-food { background:#eff6ff; color:#2563eb; }
        .badge-transport { background:#ecfdf5; color:#10b981; }
        .badge-bills { background:#fffbeb; color:#f59e0b; }
        .badge-shopping { background:#f0fdf4; color:#16a34a; }
        .badge-health { background:#fef2f2; color:#ef4444; }
        .badge-entertainment { background:#fdf4ff; color:#a855f7; }
        .badge-education { background:#eff6ff; color:#0284c7; }
        .badge-clothing { background:#fff7ed; color:#ea580c; }
        .badge-other { background:#f8fafc; color:#64748b; }
        .badge-rent { background:#fdf2f8; color:#db2777; }

        .main-inner > h1 {
            text-shadow: 0 1px 0 rgba(255,255,255,0.7);
        }

        .budget-bar { height: 8px; border-radius: 99px; background: #e2e8f0; overflow: hidden; }
        .budget-bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transition: width 1.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0;
        }

        .ripple-host { position: relative; overflow: hidden; }
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(37,99,235,0.15);
            transform: scale(0);
            animation: rippleAnim 0.5s linear forwards;
            pointer-events: none;
        }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        .enter-item { opacity: 0; transform: translateY(14px); animation: fadeUpIn 0.4s ease-out forwards; }
        @keyframes fadeUpIn { to { opacity: 1; transform: translateY(0); } }

        .toast-container {
            position: fixed; right: 1rem; bottom: 1rem; z-index: 9999;
            display: flex; flex-direction: column; gap: 0.6rem;
        }
        .toast {
            min-width: 240px; max-width: 360px;
            background: #fff; border-radius: 12px;
            border: 1px solid var(--border2);
            box-shadow: 0 10px 24px rgba(15,23,42,0.14);
            padding: 0.72rem 0.9rem;
            color: var(--txt2);
            transform: translateY(24px); opacity: 0;
            animation: toastIn 0.2s ease forwards;
        }
        .toast.success { border-left: 4px solid var(--green); }
        .toast.warning { border-left: 4px solid var(--amber); }
        .toast.error { border-left: 4px solid var(--red); }
        .toast.hide { animation: toastOut 0.2s ease forwards; }
        @keyframes toastIn { to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { to { transform: translateY(24px); opacity: 0; } }

        @media (max-width: 768px) {
            .topbar { padding: 0 12px; height: auto; min-height: 56px; flex-wrap: wrap; gap: 8px; }
            .topbar-left { width: 100%; flex-wrap: wrap; }
            .topbar-nav {
                position: static;
                transform: none;
                width: 100%;
                overflow-x: auto;
                padding-bottom: 4px;
                background: transparent;
                border: 0;
                box-shadow: none;
                padding-left: 0;
                padding-right: 0;
            }
            .main-inner { padding: 20px 14px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @if(session('user_id'))
        <div class="app-shell">
            <div class="main">
                <header class="topbar">
                    <div class="topbar-left">
                        <div class="sidebar-header" style="margin-bottom:0;">
                            <div class="sidebar-logo-circle">FT</div>
                            <div class="sidebar-title">Finance Tracker</div>
                        </div>
                        <nav class="topbar-nav">
                            <a href="{{ route('dashboard') }}"
                               class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link--active' : '' }}">
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('expenses.index') }}"
                               class="sidebar-link {{ request()->routeIs('expenses.index') ? 'sidebar-link--active' : '' }}">
                                <span>All Expenses</span>
                            </a>
                            <a href="{{ route('expenses.create') }}"
                               class="sidebar-link {{ request()->routeIs('expenses.create') ? 'sidebar-link--active' : '' }}">
                                <span>Add Expense</span>
                            </a>
                            <a href="{{ route('charts') }}"
                               class="sidebar-link {{ request()->routeIs('charts') ? 'sidebar-link--active' : '' }}">
                                <span>Analytics</span>
                            </a>
                            <a href="{{ route('reports.index') }}"
                               class="sidebar-link {{ request()->routeIs('reports.*') ? 'sidebar-link--active' : '' }}">
                                <span>Reports</span>
                            </a>
                        </nav>
                    </div>
                    <div class="topbar-right">
                        @php
                            $name = auth()->check() ? auth()->user()->name : (string) session('user_name', '');
                            $nameParts = explode(' ', trim($name));
                            $initials = '';
                            foreach ($nameParts as $part) {
                                if ($part === '') {
                                    continue;
                                }
                                $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            $initials = mb_substr($initials, 0, 2, 'UTF-8');
                        @endphp
                        <div class="sidebar-footer">
                            <div class="sidebar-user-trigger" id="user-menu-button">
                                <div style="display:flex; align-items:center; gap:0.6rem;">
                                    <div class="sidebar-user-avatar">
                                        {{ $initials !== '' ? $initials : strtoupper(mb_substr($name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:500;">{{ $name }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="sidebar-user-dropdown" id="user-menu-dropdown" style="right:0; left:auto; bottom:auto; top:2.4rem; min-width:180px;">
                                <div class="sidebar-user-dropdown-section">
                                    <a href="{{ route('profile.show') }}" class="sidebar-user-dropdown-link">
                                        Profile &amp; Settings
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="margin:0; margin-top:0.15rem;">
                                        @csrf
                                        <button type="submit" class="sidebar-user-dropdown-link">
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="main-inner">
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
            </div>
        </div>
    @else
        <div class="main" style="margin-left:0; background:var(--bg);">
            <div class="main-inner" style="max-width: 980px; margin: 0 auto;">
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
        </div>
    @endif

    <div class="toast-container" id="toast-container"></div>
    <script>
        (() => {
            const userBtn = document.getElementById("user-menu-button");
            const userDrop = document.getElementById("user-menu-dropdown");
            if (userBtn && userDrop) {
                userBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    userDrop.classList.toggle("open");
                });
                document.addEventListener("click", (e) => {
                    if (!userDrop.contains(e.target) && !userBtn.contains(e.target)) userDrop.classList.remove("open");
                });
            }

            const toastContainer = document.getElementById("toast-container");
            window.appToast = function(message, type = "success") {
                if (!toastContainer || !message) return;
                const node = document.createElement("div");
                node.className = `toast ${type}`;
                node.textContent = message;
                toastContainer.appendChild(node);
                setTimeout(() => {
                    node.classList.add("hide");
                    setTimeout(() => node.remove(), 220);
                }, 2500);
            };
            const successAlert = document.querySelector(".alert-success");
            if (successAlert?.textContent?.trim()) window.appToast(successAlert.textContent.trim(), "success");
            const errorAlert = document.querySelector(".alert-error");
            if (errorAlert?.textContent?.trim()) window.appToast("Form error detected.", "error");

            const staggerTargets = Array.from(document.querySelectorAll(".main-inner > h1, .main-inner > p, .main-inner .card, .main-inner form, .main-inner table"));
            staggerTargets.forEach((el, i) => {
                el.classList.add("enter-item");
                el.style.animationDelay = `${(i + 1) * 0.06}s`;
            });

            const rippleTargets = document.querySelectorAll(".btn, .card, .sidebar-link");
            rippleTargets.forEach((el) => {
                el.classList.add("ripple-host");
                el.addEventListener("click", (e) => {
                    const rect = el.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const ripple = document.createElement("span");
                    ripple.className = "ripple";
                    ripple.style.width = `${size}px`;
                    ripple.style.height = `${size}px`;
                    ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
                    ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
                    el.appendChild(ripple);
                    ripple.addEventListener("animationend", () => ripple.remove());
                });
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
