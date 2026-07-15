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

        /* Demo accounts panel */
        .demo-hint {
            margin-top: 24px; padding: 14px;
            background: var(--glow); border: 1px solid rgba(52,211,153,0.18);
            border-radius: 9px; font-size: 11.5px; color: var(--text-2);
        }
        .demo-toggle {
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer; user-select: none;
        }
        .demo-toggle strong { color: var(--accent-hover); font-weight: 600; }
        .demo-toggle .toggle-hint { font-size: 11px; color: var(--muted); }
        .demo-accounts { margin-top: 12px; display: none; }
        .demo-accounts.open { display: block; }
        .demo-group-label {
            font-size: 10px; font-weight: 600; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--muted);
            margin: 12px 0 4px;
        }
        .demo-group-label:first-child { margin-top: 4px; }
        .demo-row {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 9px; border-radius: 7px;
            cursor: pointer; transition: background 0.15s;
        }
        .demo-row:hover { background: rgba(52,211,153,0.07); }
        .demo-row-email { font-family: var(--font-mono); color: var(--text); font-size: 11px; flex: 1; }
        .demo-row-role {
            font-size: 10px; color: var(--muted); background: rgba(255,255,255,0.04);
            border-radius: 4px; padding: 2px 6px; white-space: nowrap;
        }
        .demo-all-pass {
            margin-top: 10px; font-size: 10.5px; color: var(--muted);
            border-top: 1px solid rgba(52,211,153,0.1); padding-top: 8px;
        }
        .demo-all-pass code { font-family: var(--font-mono); color: var(--text); }
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

        <div style="margin-top: 20px; font-size: 12px; color: var(--text-2); text-align: center;">
            Have an approved email? <a href="{{ route('register') }}" style="color: var(--accent-hover); text-decoration: none; font-weight: 600;">Create your account</a>
        </div>

        @unless(app()->isProduction())
        <div class="demo-hint">
            <div class="demo-toggle" onclick="toggleDemo()">
                <strong>Demo access</strong>
                <span class="toggle-hint" id="demo-arrow">Show all accounts ▾</span>
            </div>

            <div class="demo-accounts" id="demo-accounts">

                <div class="demo-group-label">Executive</div>
                <div class="demo-row" onclick="fillLogin('admin@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">admin@trooms.co.ke</span>
                    <span class="demo-row-role">Owner / Full Access</span>
                </div>
                <div class="demo-row" onclick="fillLogin('james@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">james@trooms.co.ke</span>
                    <span class="demo-row-role">Managing Director</span>
                </div>

                <div class="demo-group-label">Farm Operations</div>
                <div class="demo-row" onclick="fillLogin('grace@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">grace@trooms.co.ke</span>
                    <span class="demo-row-role">Horticulture Manager</span>
                </div>
                <div class="demo-row" onclick="fillLogin('peter@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">peter@trooms.co.ke</span>
                    <span class="demo-row-role">Agronomist</span>
                </div>
                <div class="demo-row" onclick="fillLogin('alice@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">alice@trooms.co.ke</span>
                    <span class="demo-row-role">Farm Supervisor</span>
                </div>

                <div class="demo-group-label">Post-Harvest &amp; Commercial</div>
                <div class="demo-row" onclick="fillLogin('john@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">john@trooms.co.ke</span>
                    <span class="demo-row-role">Packhouse Supervisor</span>
                </div>
                <div class="demo-row" onclick="fillLogin('mary@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">mary@trooms.co.ke</span>
                    <span class="demo-row-role">Quality Officer</span>
                </div>
                <div class="demo-row" onclick="fillLogin('lucy@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">lucy@trooms.co.ke</span>
                    <span class="demo-row-role">Sales Officer</span>
                </div>
                <div class="demo-row" onclick="fillLogin('daniel@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">daniel@trooms.co.ke</span>
                    <span class="demo-row-role">Driver / Logistics</span>
                </div>

                <div class="demo-group-label">Finance &amp; Stores</div>
                <div class="demo-row" onclick="fillLogin('david@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">david@trooms.co.ke</span>
                    <span class="demo-row-role">Finance Officer</span>
                </div>
                <div class="demo-row" onclick="fillLogin('samuel@trooms.co.ke')" title="Click to fill">
                    <span class="demo-row-email">samuel@trooms.co.ke</span>
                    <span class="demo-row-role">Storekeeper</span>
                </div>

                <div class="demo-all-pass">All accounts — password: <code>password</code></div>
            </div>
        </div>
        @endunless

        <script>
        function toggleDemo() {
            var panel = document.getElementById('demo-accounts');
            var arrow = document.getElementById('demo-arrow');
            panel.classList.toggle('open');
            arrow.textContent = panel.classList.contains('open')
                ? 'Hide accounts ▴'
                : 'Show all accounts ▾';
        }
        function fillLogin(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
        }
        </script>
    </div>
</body>
</html>
