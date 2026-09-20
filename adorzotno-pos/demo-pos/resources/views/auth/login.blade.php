<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Adorzotno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #060c1f;
            display: grid;
            place-items: center;
            padding: 28px;
            overflow: hidden;
            position: relative;
        }

        /* ── mesh gradient blobs ── */
        .blob {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(90px);
            opacity: 0.6;
        }
        .b1 { width:700px;height:700px; top:-200px;  left:-200px;  background:#1a3dbf; }
        .b2 { width:600px;height:600px; bottom:-200px;right:-150px; background:#0891b2; }
        .b3 { width:400px;height:400px; top:40%;     left:40%;     background:#4f46e5; opacity:0.35; }
        .b4 { width:300px;height:300px; top:10%;     right:10%;    background:#0e7490; opacity:0.3; }

        /* ── card wrapper (gradient border trick) ── */
        .glow-wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
        }

        .glow-wrap::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 30px;
            background: linear-gradient(135deg, #435ebe 0%, #41bbdd 50%, #6366f1 100%);
            z-index: -1;
            opacity: 0.9;
            filter: blur(1px);
        }

        /* soft glow behind card */
        .glow-wrap::after {
            content: '';
            position: absolute;
            inset: -20px;
            border-radius: 40px;
            background: linear-gradient(135deg, #435ebe, #41bbdd, #6366f1);
            z-index: -2;
            opacity: 0.2;
            filter: blur(40px);
        }

        /* ── the card ── */
        .card {
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
        }

        /* ── logo hero section ── */
        .hero {
            position: relative;
            padding: 40px 40px 36px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
            overflow: hidden;
        }

        /* gradient shimmer behind logo */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 120% 90% at 50% -10%, rgba(67,94,190,0.1) 0%, transparent 60%),
                radial-gradient(ellipse 80% 60% at 90% 110%, rgba(65,187,221,0.08) 0%, transparent 55%);
        }

        .hero img {
            position: relative;
            z-index: 1;
            height: 84px;
            width: auto;
            display: block;
        }

        /* gradient divider under logo */
        .hero-divider {
            width: 100%;
            height: 3px;
            margin-top: 28px;
            background: linear-gradient(90deg,
                transparent 0%,
                #435ebe 25%,
                #41bbdd 65%,
                transparent 100%
            );
        }

        /* ── form section ── */
        .body {
            padding: 32px 40px 40px;
            background: #fff;
        }

        .form-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            background: linear-gradient(90deg, #435ebe, #41bbdd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .form-h1 {
            font-size: 30px;
            font-weight: 900;
            color: #0b1336;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 24px;
        }

        /* alerts */
        .alert {
            padding: 11px 14px;
            border-radius: 11px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex; gap: 9px; align-items: flex-start; font-weight: 500;
        }
        .alert svg { flex-shrink:0; margin-top:1px; }
        .alert-error   { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
        .alert-success { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }

        /* field */
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
            letter-spacing: 0.01em;
        }

        .input-wrap { position: relative; }

        .fi {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: #d1d5db;
            pointer-events: none; display: flex;
            transition: color .2s;
        }

        .field input {
            width: 100%;
            padding: 13px 44px 13px 40px;
            border: 1.5px solid #e9ecf3;
            border-radius: 12px;
            background: #f7f9ff;
            font-size: 14px; font-family: inherit;
            color: #0b1336; outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .field input::placeholder { color: #bdc5d4; }
        .field input:focus {
            border-color: #435ebe;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(67,94,190,0.12);
        }
        .input-wrap:focus-within .fi { color: #435ebe; }

        .pw-btn {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #d1d5db; cursor: pointer;
            display: flex; padding: 3px;
            transition: color .2s;
        }
        .pw-btn:hover { color: #435ebe; }

        .field-error {
            margin-top: 5px; font-size: 12px;
            color: #dc2626;
            display: flex; align-items: center; gap: 4px;
        }

        /* remember */
        .rem-row {
            display: flex; margin: 6px 0 24px;
            font-size: 13px;
        }
        .rem-row label {
            display: flex; align-items: center;
            gap: 8px; color: #6b7280; cursor: pointer;
        }
        .rem-row input[type=checkbox] {
            width: 15px; height: 15px;
            accent-color: #435ebe; cursor: pointer;
        }

        /* button */
        .btn {
            width: 100%; padding: 15px;
            border: none; border-radius: 13px;
            background: linear-gradient(130deg, #1e3a8a 0%, #435ebe 45%, #41bbdd 100%);
            color: #fff;
            font-size: 15px; font-weight: 700;
            font-family: inherit; letter-spacing: 0.02em;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            position: relative; overflow: hidden;
            box-shadow: 0 8px 28px rgba(67,94,190,0.45);
            transition: transform .18s, box-shadow .18s, filter .18s;
        }
        /* top-left shine */
        .btn::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(130deg, rgba(255,255,255,0.22) 0%, transparent 50%);
            pointer-events: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 40px rgba(67,94,190,0.55);
            filter: brightness(1.06);
        }
        .btn:active { transform: translateY(0); }

        /* bottom tagline */
        .foot {
            text-align: center;
            margin-top: 18px;
            font-size: 11.5px;
            color: rgba(255,255,255,0.28);
            letter-spacing: 0.05em;
            font-style: italic;
        }

        @media (max-width: 500px) {
            body { padding: 14px; }
            .hero { padding: 32px 28px 28px; }
            .hero img { height: 68px; }
            .body { padding: 24px 28px 32px; }
            .form-h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

    <div class="blob b1"></div>
    <div class="blob b2"></div>
    <div class="blob b3"></div>
    <div class="blob b4"></div>

    <div class="glow-wrap">
        <div class="card">

            <div class="hero">
                <img src="{{ url('public/admin/dist/assets/compiled/png/AdorzotnoLogo.png') }}" alt="Adorzotno Limited">
                <div class="hero-divider"></div>
            </div>

            <div class="body">
                <div class="form-eyebrow">Admin Portal</div>
                <h1 class="form-h1">Sign in to<br>your account</h1>

                @if (session('status'))
                    <div class="alert alert-success">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email Address</label>
                        <div class="input-wrap">
                            <span class="fi">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   required autofocus autocomplete="username" placeholder="admin@gmail.com">
                        </div>
                        @error('email')
                            <div class="field-error">
                                <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <span class="fi">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                            <input id="password" type="password" name="password"
                                   required autocomplete="current-password" placeholder="••••••••">
                            <button type="button" class="pw-btn" onclick="togglePw()">
                                <svg id="eye-s" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg id="eye-h" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error">
                                <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="rem-row">
                        <label for="remember">
                            <input id="remember" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            Keep me signed in
                        </label>
                    </div>

                    <button class="btn" type="submit">
                        <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <p class="foot">Affection For Child, Care For All</p>
    </div>

    <script>
        function togglePw() {
            const i = document.getElementById('password');
            const s = document.getElementById('eye-s');
            const h = document.getElementById('eye-h');
            if (i.type === 'password') { i.type = 'text'; s.style.display = 'none'; h.style.display = ''; }
            else { i.type = 'password'; s.style.display = ''; h.style.display = 'none'; }
        }
    </script>
</body>
</html>
