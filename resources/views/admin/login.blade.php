<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#100b20">
    <title>Admin Login | Oversmoke Vlounge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: dark;
            font-family: 'Poppins', sans-serif;
            color: #f7f5ff;
            background: #100b20;
            font-synthesis: none;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            min-height: 100svh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow-x: hidden;
            padding: 24px;
            background:
                radial-gradient(ellipse at 50% 105%, rgba(86, 35, 213, .33), transparent 55%),
                linear-gradient(145deg, #090710, #19112f 58%, #0c0914);
        }

        body::before,
        body::after {
            position: fixed;
            z-index: 0;
            width: min(42vw, 430px);
            aspect-ratio: 1;
            border: 1px solid rgba(157, 121, 255, .12);
            border-radius: 50%;
            content: "";
            pointer-events: none;
            animation: orbit 18s ease-in-out infinite alternate;
        }

        body::before {
            top: -25%;
            left: -16%;
            box-shadow: 0 0 90px rgba(111, 63, 227, .12), inset 0 0 80px rgba(111, 63, 227, .08);
        }

        body::after {
            right: -17%;
            bottom: -34%;
            width: min(52vw, 540px);
            animation-delay: -8s;
            animation-direction: alternate-reverse;
            box-shadow: 0 0 110px rgba(111, 63, 227, .13), inset 0 0 90px rgba(111, 63, 227, .08);
        }

        .smoke {
            position: fixed;
            inset: -10%;
            z-index: 0;
            pointer-events: none;
            background: url("{{ asset('images/smoke-bg.png') }}") center / cover no-repeat;
            opacity: .31;
            animation: drift 38s ease-in-out infinite alternate;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: min(100%, 410px);
            padding: 38px 36px 30px;
            border: 1px solid rgba(151, 129, 210, .2);
            border-radius: 16px;
            background: linear-gradient(155deg, rgba(20, 17, 31, .97), rgba(11, 10, 17, .98));
            box-shadow: 0 24px 80px rgba(0, 0, 0, .56), 0 0 50px rgba(91, 48, 190, .12);
            animation: card-enter .8s cubic-bezier(.2, .75, .25, 1) both, card-glow 7s ease-in-out 1s infinite alternate;
        }

        .brand { margin-bottom: 32px; text-align: center; animation: content-enter .65s .12s both; }
        .brand img { display: block; width: min(220px, 100%); margin: 0 auto 18px; animation: logo-float 5s ease-in-out 1s infinite; }
        .eyebrow { margin: 0 0 7px; color: #a78bfa; font-size: 11px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 23px; font-weight: 600; letter-spacing: -.03em; }
        .subtitle { margin: 8px 0 0; color: #aaa5b7; font-size: 13px; }
        .field { margin-bottom: 19px; animation: content-enter .6s .24s both; }
        .field:nth-of-type(2) { animation-delay: .34s; }
        label { display: block; margin-bottom: 8px; color: #e8e4f1; font-size: 13px; font-weight: 500; }
        input[type="email"], .password-field input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #302a40;
            border-radius: 7px;
            outline: none;
            background: #111019;
            color: #fff;
            font: inherit;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s, background-color .2s, transform .2s;
        }
        input[type="email"]:hover, .password-field input:hover { border-color: #51416f; }
        input[type="email"]:focus, .password-field input:focus { transform: translateY(-1px); border-color: #8357ff; outline: none; background: #15121e; box-shadow: 0 0 0 3px rgba(112, 69, 235, .2), 0 0 22px rgba(112, 69, 235, .12); }
        .password-field { position: relative; }
        .password-field input { padding-right: 50px; }
        .password-toggle { position: absolute; top: 50%; right: 8px; display: grid; width: 34px; height: 34px; place-items: center; padding: 6px; border: 0; border-radius: 5px; background: transparent; color: #aaa4b2; cursor: pointer; transform: translateY(-50%); }
        .password-toggle:hover, .password-toggle:focus-visible { background: #211b2c; color: #fff; }
        .password-toggle:focus-visible { outline: 2px solid #c4b5fd; outline-offset: 1px; }
        .password-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .password-eye-closed, .password-toggle[aria-pressed="true"] .password-eye-open { display: none; }
        .password-toggle[aria-pressed="true"] .password-eye-closed { display: block; }
        .remember { display: inline-flex; align-items: center; gap: 9px; color: #bdb8c9; font-size: 12px; cursor: pointer; animation: content-enter .6s .42s both; }
        .remember input { width: 15px; height: 15px; margin: 0; accent-color: #7045eb; }
        .error { margin: 7px 0 0; color: #ff8e9b; font-size: 12px; }
        .alert { margin-bottom: 18px; padding: 11px 13px; border: 1px solid rgba(255, 102, 127, .3); border-radius: 7px; background: rgba(130, 30, 50, .15); color: #ff9aa7; font-size: 12px; }
        .submit {
            width: 100%;
            min-height: 47px;
            margin-top: 24px;
            border: 0;
            border-radius: 7px;
            background: linear-gradient(100deg, #5426d8, #743cf2);
            box-shadow: 0 7px 22px rgba(92, 48, 218, .24);
            color: #fff;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .09em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: content-enter .6s .5s both;
            transition: filter .2s, transform .2s, box-shadow .2s;
        }
        .submit::before {
            position: absolute;
            top: -70%;
            left: -65%;
            width: 42%;
            height: 240%;
            transform: rotate(25deg);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .28), transparent);
            content: "";
            transition: left .65s ease;
        }
        .submit:hover { filter: brightness(1.13); box-shadow: 0 9px 28px rgba(92, 48, 218, .4); }
        .submit:hover::before { left: 130%; }
        .submit:active { transform: translateY(1px); }
        .submit:focus-visible, .back-link:focus-visible, .recovery-link:focus-visible { outline: 2px solid #c4b5fd; outline-offset: 3px; }
        .divider { height: 1px; margin: 24px 0 18px; border: 0; background: #282432; animation: content-enter .6s .56s both; }
        .back-link { display: block; color: #b9a2ff; font-size: 12px; text-align: center; text-decoration: none; animation: content-enter .6s .62s both; transition: color .2s, transform .2s; }
        .back-link:hover { color: #ded3ff; }
        .recovery-link { display: block; margin-top: 16px; color: #b9a2ff; font-size: 12px; text-align: center; text-decoration: none; animation: content-enter .6s .58s both; transition: color .2s; }
        .recovery-link:hover { color: #ded3ff; }
        .status { margin: 0 0 18px; padding: 11px 13px; border: 1px solid rgba(112, 220, 170, .26); border-radius: 7px; background: rgba(30, 130, 80, .12); color: #9de2bd; font-size: 12px; }
        .signup-link { display: block; margin-top: 12px; color: #ded3ff; font-size: 12px; text-align: center; text-decoration: none; animation: content-enter .6s .7s both; transition: color .2s; }
        .signup-link:hover { color: #fff; }
        .secure-note { margin: 18px 0 0; color: #716b7d; font-size: 10px; text-align: center; animation: content-enter .6s .68s both; }

        @keyframes drift {
            from { transform: translate3d(-2%, -1%, 0) scale(1.04); }
            to { transform: translate3d(2%, 1%, 0) scale(1.12); }
        }

        @keyframes card-enter {
            from { opacity: 0; transform: translateY(22px) scale(.97); filter: blur(7px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        @keyframes content-enter {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes card-glow {
            from { box-shadow: 0 24px 80px rgba(0, 0, 0, .56), 0 0 38px rgba(91, 48, 190, .1); }
            to { box-shadow: 0 24px 80px rgba(0, 0, 0, .56), 0 0 62px rgba(111, 63, 227, .2); }
        }

        @keyframes logo-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes orbit {
            from { transform: translate3d(-2%, 0, 0) scale(.98); }
            to { transform: translate3d(3%, 2%, 0) scale(1.04); }
        }

        @media (max-width: 480px) {
            body { padding: 16px; }
            .login-card { padding: 32px 24px 26px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <div class="smoke" aria-hidden="true"></div>

    <main class="login-card">
        <header class="brand">
            <img src="{{ asset('images/oversmokev-logo.png') }}" alt="Oversmoke Vlounge">
            <p class="eyebrow">Admin portal</p>
            <h1>Welcome back</h1>
            <p class="subtitle">Owners and staff can sign in to manage the store.</p>
        </header>

        @if ($errors->any())
            <div class="alert" role="alert">
                {{ $errors->first() }}
            </div>
        @endif
        @if (session('status'))
            <div class="status" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="field">
                <label for="email">Owner or staff email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                >
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">
                        <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7s-3.5 7-10 7c-1.4 0-2.7-.3-3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <label class="remember" for="remember">
                <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                Keep me signed in
            </label>

            <button class="submit" type="submit">SIGN IN</button>
        </form>

        <a class="recovery-link" href="{{ route('admin.password.request') }}">Forgot your password?</a>
        <hr class="divider">
        @if ($adminRegistrationOpen)
            <a class="signup-link" href="{{ route('admin.register') }}">Owner? Create the first admin account</a>
        @endif
        <p class="secure-note">Authorized administrators only</p>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(button => {
            const input = document.getElementById(button.getAttribute('aria-controls'));
            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
