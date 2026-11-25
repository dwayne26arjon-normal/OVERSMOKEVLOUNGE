<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | OversmokeVlounge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #120c2a;
            --bg-mid: #2a2058;
            --card: #0b0b0b;
            --card-border: #1b2340;
            --field: #141414;
            --field-border: #232a3d;
            --primary: #4f1ff0;
            --primary-hover: #6439ff;
            --link: #8b5cf6;
            --text: #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            background: linear-gradient(160deg, var(--bg-dark), var(--bg-mid));
        }


        /* Animated smoke (plain CSS, no Tailwind needed) */
        .smoke {
            position: fixed;
            inset: -10%;
            z-index: 0;
            pointer-events: none;
            background: url("{{ asset('images/smoke-bg.png') }}") center / cover no-repeat;
        }
        .smoke.a { animation: drift-a 20s ease-in-out infinite alternate; }
        .smoke.b { opacity: .5; animation: drift-b 28s ease-in-out infinite alternate; }

        @keyframes drift-a {
            from { transform: translate3d(-8%, -5%, 0) scale(1.1); }
            to   { transform: translate3d(8%, 5%, 0) scale(1.3); }
        }
        @keyframes drift-b {
            from { transform: scaleX(-1) translate3d(8%, 5%, 0) scale(1.2); }
            to   { transform: scaleX(-1) translate3d(-8%, -6%, 0) scale(1.4); }
        }

        .wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1500px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 130px;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 40px;
        }

        .brand { text-align: center; margin-bottom: 32px; }
        .brand img { width: 260px; max-width: 100%; display: block; margin: 0 auto 4px; }
        .brand h1 { font-size: 24px; font-weight: 600; }

        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 16px; font-weight: 600; margin-bottom: 8px; }

        .input-wrap { position: relative; }

        .input-wrap input {
            width: 100%;
            height: 52px;
            background: var(--field);
            border: 1px solid var(--field-border);
            border-radius: 8px;
            padding: 0 52px 0 16px;
            font: inherit;
            font-size: 16px;
            color: var(--text);
        }

        .input-wrap input:-webkit-autofill,
        .input-wrap input:-webkit-autofill:hover,
        .input-wrap input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text);
            -webkit-box-shadow: 0 0 0 1000px var(--field) inset;
            caret-color: var(--text);
        }

        .input-wrap input:focus {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
            border-color: transparent;
        }

        .toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: none;
            border: 0;
            border-radius: 6px;
            color: var(--text);
            cursor: pointer;
            display: grid;
            place-items: center;
        }

        .toggle:focus-visible { outline: 2px solid var(--primary); }
        .toggle .eye-open { display: none; }
        .toggle.showing .eye-open { display: block; }
        .toggle.showing .eye-closed { display: none; }

        .error { color: #ff6b6b; font-size: 12px; margin-top: 6px; }

        .btn {
            display: block;
            width: min(280px, 100%);
            height: 50px;
            margin: 28px auto 16px;
            background: var(--primary);
            color: var(--text);
            border: 0;
            border-radius: 10px;
            font: inherit;
            font-size: 19px;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
        }

        .btn:hover { background: var(--primary-hover); }
        .btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }

        .signup { text-align: center; font-size: 14px; font-weight: 600; }
        .signup a { color: var(--link); text-decoration: none; }
        .signup a:hover { text-decoration: underline; }
        .legal-links { margin-top: 16px; color: #c8c3d4; font-size: 12px; text-align: center; }
        .legal-links a { color: var(--link); text-decoration: none; }
        .legal-links a:hover { text-decoration: underline; }
        .forgot-link { display: block; margin-top: 10px; color: var(--link); font-size: 13px; text-align: center; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }
        .status { margin-bottom: 16px; padding: 10px 12px; border: 1px solid rgba(88, 200, 145, .3); border-radius: 7px; background: rgba(25, 115, 72, .14); color: #8de0b0; font-size: 12px; text-align: center; }

        .mascot { flex: 0 1 580px; min-width: 0; }
        .mascot img { width: 100%; max-width: 580px; display: block; }


        @media (max-width: 1000px) {
            .mascot { display: none; }
            .wrapper { justify-content: center; }
        }

        @media (max-width: 600px) {
            body { padding: 16px; }
            .card { padding: 30px 22px 26px; border-radius: 16px; }
            .brand { margin-bottom: 30px; }
            .brand img { width: min(240px, 100%); }
            .brand h1 { font-size: 22px; }
            .field { margin-bottom: 20px; }
            .field label { margin-bottom: 8px; font-size: 16px; }
            .input-wrap input { height: 54px; padding: 0 54px 0 14px; font-size: 16px; }
            .toggle { width: 42px; height: 42px; }
            .btn { height: 50px; margin: 28px auto 16px; font-size: 18px; }
            .signup { font-size: 14px; }
        }

    </style>
</head>
<body>
    <!-- Animated smoke layers -->
    <div class="smoke a"></div>
    <div class="smoke b"></div>

    <main class="wrapper">
        <form class="card" data-smoke-block method="POST" action="{{ url('/login') }}">
            @csrf

            <div class="brand">
                <img src="{{ asset('images/oversmokev-logo.png') }}" alt="OversmokeVlounge">
                <h1>Login into your account</h1>
            </div>

            @if (session('status'))
                <div class="status" role="status">{{ session('status') }}</div>
            @endif

            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="toggle" id="togglePassword" aria-label="Show password">
                        <svg class="eye-closed" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                        <svg class="eye-open" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn">LOGIN</button>

            <a class="forgot-link" href="{{ route('password.request') }}">Forgot your password?</a>
            <p class="signup">Don't have an account yet? <a href="{{ url('/go/register') }}">Sign up</a></p>
            <p class="legal-links">
                <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
            </p>
        </form>

        <div class="mascot">
            <x-user.vape-mascot />
        </div>
    </main>

    <script>
        // Show / hide password
        const toggle = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.classList.toggle('showing', show);
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>