<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — THE ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0c1210; --card: #131c18; --input: #0e1512;
            --border: #223029; --border-strong: #2c3d34;
            --text: #eaf2ee; --text-2: #9db3a8; --muted: #647a70;
            --accent: #34d399; --accent-hover: #6ee7b7; --accent-strong: #10b981;
            --glow: rgba(52,211,153,0.14);
            --font-display: 'Bricolage Grotesque', 'Inter', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            animation: rise 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
        .login-logo {
            width: 46px; height: 46px;
            background: var(--accent-strong);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #08120d;
            flex-shrink: 0;
        }
        .brand-text h1 { font-family: var(--font-display); font-size: 17px; font-weight: 700; letter-spacing: -0.4px; }
        .brand-text span { font-size: 11px; color: var(--muted); }
        h2 { font-family: var(--font-display); font-size: 22px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 6px; }
        .subtitle { font-size: 13px; color: var(--muted); margin-bottom: 28px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 12.5px; font-weight: 500; color: var(--text-2); margin-bottom: 6px; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 11px 14px;
            background: var(--input); border: 1px solid var(--border); border-radius: 9px;
            color: var(--text); font-size: 13px; font-family: inherit;
            transition: all 0.2s;
        }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--glow); }
        .remember { display: flex; align-items: center; gap: 8px; margin-bottom: 22px; }
        .remember input { accent-color: var(--accent); width: 15px; height: 15px; }
        .remember label { margin: 0; font-size: 12px; color: var(--text-2); }
        .btn-login {
            width: 100%; padding: 12px;
            background: var(--accent); color: #08120d; border: none; border-radius: 9px;
            font-size: 14px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-login:hover { background: var(--accent-hover); }
        .error { background: rgba(240,101,90,0.1); color: #f0655a; border: 1px solid rgba(240,101,90,0.22); padding: 10px 14px; border-radius: 9px; font-size: 12px; margin-bottom: 20px; }
        .demo-hint {
            margin-top: 24px; padding: 13px 14px;
            background: var(--glow); border: 1px solid rgba(52,211,153,0.18);
            border-radius: 9px; font-size: 11.5px; color: var(--text-2);
        }
        .demo-hint strong { color: var(--accent-hover); font-weight: 600; }
        .demo-hint code { font-family: var(--font-mono); color: var(--text); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="login-logo">
                <x-icon name="crops" size="26" />
            </div>
            <div class="brand-text">
                <h1>Trooms ERP</h1>
                <span>Horticulture Management</span>
            </div>
        </div>

        <h2>Sign in</h2>
        <p class="subtitle">From nursery to dispatch — one farm system.</p>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="remember">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Keep me signed in</label>
            </div>
            <button type="submit" class="btn-login">Sign in</button>
        </form>

        <div class="demo-hint">
            <strong>Demo access</strong><br>
            <code>admin@trooms.co.ke</code> / <code>password</code>
        </div>
    </div>
</body>
</html>
