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
            --bg: #080d0b;
            --bg2: #0c1310;
            --surface: #141f1a;
            --surface2: #1a2e26;
            --border: rgba(74, 222, 128, 0.14);
            --border2: rgba(74, 222, 128, 0.26);
            --acc: #22c55e;
            --acc2: #15803d;
            --acc-light: rgba(34, 197, 94, 0.14);
            --acc-glow: rgba(34, 197, 94, 0.22);
            --green: #4ade80;
            --green-light: rgba(34, 197, 94, 0.14);
            --amber: #86efac;
            --amber-light: rgba(134, 239, 172, 0.12);
            --red: #f87171;
            --red-light: rgba(248, 113, 113, 0.12);
            --txt: #ecfdf5;
            --txt2: #a8d5c3;
            --muted: #6b9088;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
            margin: 0;
            color: var(--txt);
            min-height: 100vh;
            background:
                radial-gradient(820px 380px at 82% -4%, rgba(34, 197, 94, 0.09) 0%, transparent 72%),
                radial-gradient(660px 320px at 10% 4%, rgba(21, 128, 61, 0.14) 0%, transparent 76%),
                linear-gradient(180deg, #0c1210 0%, #080d0b 55%, #050807 100%);
        }
        .mono, .currency-value, .number-value, .date-cell, .amount-cell {
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .app-shell, .main { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar {
            background: rgba(12, 18, 15, 0.92);
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
            box-shadow: 0 10px 34px rgba(0, 0, 0, 0.45);
        }
        .topbar-left { display:flex; align-items:center; gap: 12px; }
        .topbar-right { display:flex; align-items:center; gap: 10px; color: var(--txt2); }
        .sidebar-header { display:flex; align-items:center; gap:8px; }
        .sidebar-logo-circle {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, #15803d, #22c55e);
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
            background: rgba(20, 31, 26, 0.95);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 4px 6px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.35);
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
            color: #052e16;
            background: linear-gradient(90deg, var(--acc), #4ade80);
            font-weight: 600;
            box-shadow: 0 8px 22px rgba(34, 197, 94, 0.28);
        }
        .sidebar-link--active::after { display: none; }
        .sidebar-user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #15803d, #4ade80);
            color: #052e16; display:inline-flex; align-items:center; justify-content:center; font-weight:700;
        }
        .sidebar-footer { position: relative; }
        .sidebar-user-trigger { cursor:pointer; }
        .sidebar-user-dropdown {
            position:absolute; right:0; top:2.6rem; min-width: 200px;
            background: var(--surface); border:1px solid var(--border2); border-radius: 12px;
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.5);
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
            box-shadow: 0 4px 22px rgba(0,0,0,0.35), 0 16px 44px rgba(0,0,0,0.2), 0 1px 0 rgba(74,222,128,0.06) inset;
            padding: 20px 24px;
            margin-bottom: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 44px;
            background: linear-gradient(180deg, rgba(34,197,94,0.08), rgba(34,197,94,0));
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
            color: #052e16;
            box-shadow: 0 8px 22px rgba(34, 197, 94, 0.28);
        }
        .btn-primary:hover {
            box-shadow: 0 10px 28px rgba(34, 197, 94, 0.38);
            transform: translateY(-1px);
            filter: brightness(1.06);
        }
        .btn-primary:active { transform: scale(0.97); }
        .btn-secondary {
            background: var(--surface2);
            border: 1px solid var(--border2);
            color: var(--txt2);
            font-weight: 500;
        }
        .btn-secondary:hover { background: rgba(34, 197, 94, 0.1); border-color: var(--acc); color: var(--txt); }

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
            background: var(--surface2);
            border: 1.5px solid var(--border2);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            color: var(--txt);
            width: 100%;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--acc);
        }
        .form-group input:focus-visible,
        .form-group select:focus-visible,
        .form-group textarea:focus-visible {
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.55);
        }
        .form-group input:focus:not(:focus-visible),
        .form-group select:focus:not(:focus-visible),
        .form-group textarea:focus:not(:focus-visible) {
            box-shadow: none;
        }
        .form-group textarea { min-height: 90px; resize: vertical; }

        .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.92rem; }
        .alert-success { background: var(--green-light); color: #bbf7d0; border: 1px solid rgba(74, 222, 128, 0.35); }
        .alert-error { background: var(--red-light); color: #fecaca; border: 1px solid rgba(248, 113, 113, 0.35); }
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

        tbody tr {
            transition: background-color 0.14s ease;
        }
        tbody tr:hover {
            background: rgba(34, 197, 94, 0.07);
        }

        .empty-state {
            text-align: center;
            padding: 2.25rem 1.5rem 2.5rem;
            border: 1px dashed var(--border2);
            border-radius: 16px;
            background: rgba(34, 197, 94, 0.05);
            max-width: 420px;
            margin-inline: auto;
        }
        .empty-state--wide { max-width: none; }
        .empty-state__icon {
            font-size: 2.75rem;
            line-height: 1;
            margin-bottom: 0.75rem;
            opacity: 0.9;
            filter: grayscale(0.15);
        }
        .empty-state__title {
            font-weight: 600;
            font-size: 1.05rem;
            margin: 0 0 0.4rem;
            color: var(--txt);
        }
        .empty-state__text {
            margin: 0;
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.5;
            max-width: 24rem;
            margin-inline: auto;
        }
        .empty-state__text a { color: var(--acc); font-weight: 500; }

        .badge-category {
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
        }
        .badge-food { background: rgba(34,197,94,0.14); color: #86efac; border: 1px solid rgba(74,222,128,0.2); }
        .badge-transport { background: rgba(34,197,94,0.18); color: #4ade80; border: 1px solid rgba(74,222,128,0.22); }
        .badge-bills { background: rgba(34,197,94,0.10); color: #bbf7d0; border: 1px solid rgba(74,222,128,0.18); }
        .badge-shopping { background: rgba(34,197,94,0.22); color: #22c55e; border: 1px solid rgba(74,222,128,0.28); }
        .badge-health { background: rgba(34,197,94,0.16); color: #a7f3d0; border: 1px solid rgba(74,222,128,0.2); }
        .badge-entertainment { background: rgba(34,197,94,0.12); color: #6ee7b7; border: 1px solid rgba(74,222,128,0.18); }
        .badge-education { background: rgba(34,197,94,0.20); color: #34d399; border: 1px solid rgba(74,222,128,0.24); }
        .badge-clothing { background: rgba(34,197,94,0.08); color: #d1fae5; border: 1px solid rgba(74,222,128,0.16); }
        .badge-other { background: rgba(15, 23, 20, 0.65); color: var(--muted); border: 1px solid var(--border); }
        .badge-rent { background: rgba(34,197,94,0.24); color: #ecfdf5; border: 1px solid rgba(74,222,128,0.3); }

        .main-inner > h1 {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
        }

        .budget-bar { height: 8px; border-radius: 99px; background: rgba(15, 23, 20, 0.9); overflow: hidden; border: 1px solid var(--border); }
        .budget-bar-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #15803d, #22c55e);
            transition: width 1.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0;
        }

        .enter-item { opacity: 0; transform: translateY(14px); animation: fadeUpIn 0.4s ease-out forwards; }
        @keyframes fadeUpIn { to { opacity: 1; transform: translateY(0); } }

        .toast-container {
            position: fixed; right: 1rem; bottom: 1rem; z-index: 9999;
            display: flex; flex-direction: column; gap: 0.6rem;
        }
        .toast {
            min-width: 240px; max-width: 360px;
            background: var(--surface); border-radius: 12px;
            border: 1px solid var(--border2);
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.45);
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
        })();
    </script>

    @stack('scripts')
</body>
</html>
