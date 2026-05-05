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
            --surface: #141f1a;
            --surface2: #1a2e26;
            --border: rgba(74, 222, 128, 0.14);
            --border2: rgba(74, 222, 128, 0.26);
            --acc: #22c55e;
            --acc2: #15803d;
            --acc-light: rgba(34, 197, 94, 0.14);
            --green: #4ade80;
            --green-light: rgba(34, 197, 94, 0.14);
            --red: #f87171;
            --red-light: rgba(248, 113, 113, 0.12);
            --txt: #ecfdf5;
            --txt2: #a8d5c3;
            --muted: #6b9088;
        }
        * { box-sizing: border-box; }

        body.auth-page {
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
            margin: 0;
            color: var(--txt);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background:
                radial-gradient(820px 380px at 82% -4%, rgba(34, 197, 94, 0.1) 0%, transparent 74%),
                radial-gradient(660px 320px at 10% 4%, rgba(21, 128, 61, 0.14) 0%, transparent 76%),
                linear-gradient(180deg, #0c1210 0%, #080d0b 55%, #050807 100%);
        }

        #auth-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .auth-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 24px;
        }

        body.auth-page .card {
            position: relative;
            z-index: 1;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 0;
        }

        h1 { margin: 0 0 14px; font-size: 24px; font-weight: 600; color: var(--txt); }

        .auth-typing-wrap {
            min-height: 3.25rem;
            margin-bottom: 1.15rem;
            text-align: center;
        }
        .auth-typing {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: var(--txt2);
            letter-spacing: 0.02em;
            line-height: 1.45;
        }
        .auth-typing #auth-typing-text {
            color: #86efac;
        }
        .auth-caret {
            display: inline-block;
            margin-left: 2px;
            color: var(--acc);
            font-weight: 300;
            animation: authCaretBlink 0.95s step-end infinite;
        }
        @keyframes authCaretBlink {
            50% { opacity: 0; }
        }

        .card {
            position: relative;
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 22px rgba(0,0,0,0.35), 0 16px 44px rgba(0,0,0,0.2), 0 1px 0 rgba(74,222,128,0.06) inset;
            padding: 20px 24px;
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
        .form-group input {
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
        .form-group input:focus {
            outline: none;
            border-color: var(--acc);
        }
        .form-group input:focus-visible {
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.55);
        }
        .form-group input:focus:not(:focus-visible) {
            box-shadow: none;
        }

        .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.92rem; }
        .alert-success { background: var(--green-light); color: #bbf7d0; border: 1px solid rgba(74, 222, 128, 0.35); }
        .alert-error { background: var(--red-light); color: #fecaca; border: 1px solid rgba(248, 113, 113, 0.35); }
        .text-danger { color: var(--red); font-size: 0.85rem; margin-top: 0.25rem; }

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
        .toast.error { border-left: 4px solid var(--red); }
        .toast.hide { animation: toastOut 0.2s ease forwards; }
        @keyframes toastIn { to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { to { transform: translateY(24px); opacity: 0; } }

    </style>
    @stack('styles')
</head>
<body class="auth-page">
<canvas id="auth-canvas"></canvas>
<div class="auth-content">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
        </div>
    @endif
    <div class="auth-typing-wrap" aria-live="polite">
        <p class="auth-typing" id="auth-typing-line">
            <span id="auth-typing-text"></span><span class="auth-caret" aria-hidden="true">|</span>
        </p>
    </div>
    @yield('content')
</div>

<div class="toast-container" id="toast-container"></div>
<script>
(() => {
    const canvas = document.getElementById('auth-canvas');
    if (!canvas || !canvas.getContext) return;
    const ctx = canvas.getContext('2d');
    const SYMBOL_POOL = ['$', '€', '£', '¥', '₺', '₿', '%', '↑', '↓', '→', '+', '−', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    let symbols = [];

    function rand(min, max) {
        return min + Math.random() * (max - min);
    }

    function buildSymbols() {
        symbols = [];
        const w = canvas.width;
        const h = canvas.height;
        for (let i = 0; i < 70; i++) {
            symbols.push({
                x: Math.random() * w,
                y: Math.random() * h,
                speed: rand(0.4, 1.2),
                size: rand(12, 24),
                opacity: rand(0.14, 0.38),
                symbol: SYMBOL_POOL[Math.floor(Math.random() * SYMBOL_POOL.length)],
                drift: rand(-0.3, 0.3),
            });
        }
    }

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        buildSymbols();
    }

    function frame() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const w = canvas.width;
        const h = canvas.height;

        for (const s of symbols) {
            s.y += s.speed;
            s.x += s.drift;
            if (s.y > h + 30) {
                s.y = -30;
                s.x = Math.random() * w;
            }
            if (s.x < -20 || s.x > w + 20) {
                s.drift *= -1;
            }
        }

        for (const s of symbols) {
            ctx.font = `${s.size}px monospace`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = `rgba(74, 222, 128, ${s.opacity})`;
            ctx.fillText(s.symbol, s.x, s.y);
        }

        const n = symbols.length;
        for (let i = 0; i < n; i++) {
            for (let j = i + 1; j < n; j++) {
                const a = symbols[i];
                const b = symbols[j];
                const dx = a.x - b.x;
                const dy = a.y - b.y;
                const dist = Math.hypot(dx, dy);
                if (dist < 100) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(74, 222, 128, 0.14)';
                    ctx.lineWidth = 0.5;
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(frame);
    }

    window.addEventListener('resize', resize);
    resize();
    requestAnimationFrame(frame);
})();

(() => {
    const toastContainer = document.getElementById('toast-container');
    window.appToast = function(message, type = 'success') {
        if (!toastContainer || !message) return;
        const node = document.createElement('div');
        node.className = `toast ${type}`;
        node.textContent = message;
        toastContainer.appendChild(node);
        setTimeout(() => {
            node.classList.add('hide');
            setTimeout(() => node.remove(), 220);
        }, 2500);
    };
    const successAlert = document.querySelector('.alert-success');
    if (successAlert?.textContent?.trim()) window.appToast(successAlert.textContent.trim(), 'success');
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert?.textContent?.trim()) window.appToast('Form error detected.', 'error');
})();

(() => {
    const el = document.getElementById('auth-typing-text');
    if (!el) return;
    const phrases = [
        'Know where every ₺ goes.',
        'Budget smarter, not harder.',
        'Receipt in—insight out.',
        'Your money, mapped clearly.',
        'Small habits. Big clarity.',
    ];
    let phraseIndex = 0;
    let charIndex = 0;
    let phase = 'typing';
    const typeDelay = 46;
    const deleteDelay = 26;
    const holdAfterType = 2400;
    const holdAfterClear = 360;

    function step() {
        const full = phrases[phraseIndex % phrases.length];
        if (phase === 'typing') {
            if (charIndex < full.length) {
                charIndex += 1;
                el.textContent = full.slice(0, charIndex);
                setTimeout(step, typeDelay);
            } else {
                phase = 'holding';
                setTimeout(step, holdAfterType);
            }
        } else if (phase === 'holding') {
            phase = 'deleting';
            step();
        } else if (phase === 'deleting') {
            if (charIndex > 0) {
                charIndex -= 1;
                el.textContent = full.slice(0, charIndex);
                setTimeout(step, deleteDelay);
            } else {
                phraseIndex += 1;
                phase = 'between';
                setTimeout(step, holdAfterClear);
            }
        } else {
            phase = 'typing';
            step();
        }
    }
    step();
})();
</script>
@stack('scripts')
</body>
</html>
