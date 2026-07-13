<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account — THE ERP</title>
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
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            -webkit-font-smoothing: antialiased;
        }
        .login-card {
            width: 100%; max-width: 440px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 16px; padding: 40px;
            animation: rise 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
        .login-logo {
            width: 46px; height: 46px; background: var(--accent-strong);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            color: #08120d; flex-shrink: 0;
        }
        .brand-text h1 { font-family: var(--font-display); font-size: 17px; font-weight: 700; letter-spacing: -0.4px; }
        .brand-text span { font-size: 11px; color: var(--muted); }
        h2 { font-family: var(--font-display); font-size: 22px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 6px; }
        .subtitle { font-size: 13px; color: var(--muted); margin-bottom: 28px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 12.5px; font-weight: 500; color: var(--text-2); margin-bottom: 6px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 11px 14px;
            background: var(--input); border: 1px solid var(--border); border-radius: 9px;
            color: var(--text); font-size: 13px; font-family: inherit; transition: all 0.2s;
        }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--glow); }
        .btn-login {
            width: 100%; padding: 12px; margin-top: 4px;
            background: var(--accent); color: #08120d; border: none; border-radius: 9px;
            font-size: 14px; font-weight: 700; font-family: inherit; cursor: pointer; transition: all 0.2s;
        }
        .btn-login:hover { background: var(--accent-hover); }
        .error { background: rgba(240,101,90,0.1); color: #f0655a; border: 1px solid rgba(240,101,90,0.22); padding: 10px 14px; border-radius: 9px; font-size: 12px; margin-bottom: 20px; }
        .hint { margin-top: 20px; font-size: 12px; color: var(--text-2); text-align: center; }
        .hint a { color: var(--accent-hover); text-decoration: none; font-weight: 600; }
        .hint a:hover { text-decoration: underline; }
        .note {
            margin-top: 22px; padding: 12px 14px;
            background: var(--glow); border: 1px solid rgba(52,211,153,0.18);
            border-radius: 9px; font-size: 11.5px; color: var(--text-2); line-height: 1.5;
        }
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

        <h2>Create your account</h2>
        <p class="subtitle">Registration is limited to emails approved by your administrator.</p>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Approved email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn-login">Create account</button>
        </form>

        <div class="note">
            Your role and permissions are assigned by your administrator when your email is approved — you don't choose them here.
        </div>

        <div class="hint">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
</body>
</html>
