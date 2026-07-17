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
            max-width: 440px;
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
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%; padding: 11px 14px;
            background: var(--input); border: 1px solid var(--border); border-radius: 9px;
            color: var(--text); font-size: 16px; font-family: inherit;
            transition: all 0.2s;
        }
        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--glow); }
        /* Browser autofill (yellow/white bg + black text) fights the dark theme. */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text);
            -webkit-box-shadow: 0 0 0px 1000px var(--input) inset;
            box-shadow: 0 0 0px 1000px var(--input) inset;
            transition: background-color 5000s ease-in-out 0s;
        }
        .password-field { position: relative; }
        .password-field input { padding-right: 42px; }
        .password-toggle {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-2); cursor: pointer;
            padding: 4px; display: flex; align-items: center; justify-content: center;
        }
        .password-toggle:hover { color: var(--text); }
        .btn-login[disabled] { opacity: 0.65; cursor: not-allowed; }
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
        .success { background: var(--glow); color: var(--accent-hover); border: 1px solid rgba(52,211,153,0.22); padding: 10px 14px; border-radius: 9px; font-size: 12px; margin-bottom: 20px; }
        .forgot-link { text-align: right; margin: -8px 0 18px; }
        .forgot-link a { font-size: 12px; color: var(--text-2); text-decoration: none; }
        .forgot-link a:hover { color: var(--accent-hover); text-decoration: underline; }

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

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

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
                <div class="password-field">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                        <span class="icon-eye"><x-icon name="eye" size="17" /></span>
                        <span class="icon-eye-off" style="display:none;"><x-icon name="eye-off" size="17" /></span>
                    </button>
                </div>
            </div>
            <div class="forgot-link">
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="remember">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Keep me signed in</label>
            </div>
            <button type="submit" class="btn-login" id="login-submit">Sign in</button>
        </form>

        <div style="margin-top: 20px; font-size: 12px; color: var(--text-2); text-align: center;">
            Have an approved email? <a href="{{ route('register') }}" style="color: var(--accent-hover); text-decoration: none; font-weight: 600;">Create your account</a>
        </div>

    </div>

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-toggle-password'));
                var willShow = input.type === 'password';
                input.type = willShow ? 'text' : 'password';
                btn.setAttribute('aria-pressed', String(willShow));
                btn.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
                btn.querySelector('.icon-eye').style.display = willShow ? 'none' : '';
                btn.querySelector('.icon-eye-off').style.display = willShow ? '' : 'none';
            });
        });

        // Guard against double-submission (e.g. a slow double-tap on mobile),
        // which can burn through the login rate limiter on a correct password.
        var loginForm = document.querySelector('form[action="{{ route('login') }}"]');
        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                var btn = document.getElementById('login-submit');
                if (btn.dataset.submitted) {
                    return;
                }
                btn.dataset.submitted = 'true';
                btn.disabled = true;
                btn.textContent = 'Signing in…';
            });
        }
    </script>
</body>
</html>
