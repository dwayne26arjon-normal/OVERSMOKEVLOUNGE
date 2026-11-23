<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password | Oversmoke Vlounge</title>
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 20px; background: #100b20; color: #f7f5ff; font-family: Arial, sans-serif; }
        main { width: min(100%, 430px); padding: 32px; border: 1px solid #302a40; border-radius: 12px; background: #15121e; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { color: #aaa5b7; font-size: 13px; line-height: 1.6; }
        label { display: block; margin: 18px 0 7px; font-size: 13px; }
        input { width: 100%; padding: 12px; border: 1px solid #302a40; border-radius: 6px; background: #111019; color: white; }
        .password-field { position: relative; }
        .password-field input { padding-right: 48px; }
        .password-toggle { position: absolute; top: 50%; right: 7px; display: grid; width: 34px; height: 34px; place-items: center; margin: 0; padding: 6px; border: 0; border-radius: 5px; background: transparent; color: #aaa4b2; cursor: pointer; transform: translateY(-50%); }
        .password-toggle:hover, .password-toggle:focus-visible { background: #211b2c; color: #fff; }
        .password-toggle:focus-visible { outline: 2px solid #c4b5fd; outline-offset: 1px; }
        .password-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .password-eye-closed, .password-toggle[aria-pressed="true"] .password-eye-open { display: none; }
        .password-toggle[aria-pressed="true"] .password-eye-closed { display: block; }
        button { width: 100%; margin-top: 18px; padding: 12px; border: 0; border-radius: 6px; background: #6537e9; color: white; font-weight: bold; cursor: pointer; }
        .error { color: #ff8e9b; font-size: 12px; }
    </style>
</head>
<body>
    <main>
        <h1>Choose a new password</h1>
        <p>Set a new password for {{ $email }}.</p>
        <form method="POST" action="{{ route('admin.password.store') }}">
            @csrf
            <label for="password">New password</label>
            <div class="password-field">
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required autofocus>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">
                    <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7s-3.5 7-10 7c-1.4 0-2.7-.3-3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            @error('password') <p class="error">{{ $message }}</p> @enderror
            <label for="password_confirmation">Confirm new password</label>
            <div class="password-field">
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Show password" aria-pressed="false">
                    <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7s-3.5 7-10 7c-1.4 0-2.7-.3-3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <button type="submit">Reset password</button>
        </form>
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
