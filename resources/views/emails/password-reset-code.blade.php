<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password reset code</title>
</head>
<body style="margin:0;padding:24px 12px;background-color:#100b1f;color:#f5f2fb;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Your Oversmoke Vlounge password reset code.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:560px;border:1px solid #34294d;border-radius:12px;background-color:#171222;border-collapse:separate;">
                    <tr>
                        <td align="center" style="padding:28px 24px 12px;">
                            <a href="{{ config('app.url') }}" style="text-decoration:none;">
                                <img src="{{ asset('images/oversmokev-logo.png') }}" width="220" alt="Oversmoke Vlounge" style="display:block;width:220px;max-width:100%;height:auto;border:0;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 30px;">
                            <p style="margin:0 0 8px;color:#b6a5ff;font-size:11px;font-weight:bold;letter-spacing:2px;text-align:center;">ACCOUNT SECURITY</p>
                            <h1 style="margin:0 0 14px;color:#fff;font-size:22px;font-weight:600;text-align:center;">Password reset code</h1>
                            <p style="margin:0 0 18px;color:#c5bfd0;font-size:14px;line-height:1.6;text-align:center;">
                                Enter this code to reset your {{ $accountType }} account password.
                            </p>
                            <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 20px;border:1px solid #493574;border-radius:8px;background-color:#100b1f;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 22px;color:#d4caff;font-family:Consolas,Monaco,monospace;font-size:28px;font-weight:bold;letter-spacing:8px;text-align:center;">{{ $code }}</td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px;color:#aaa3b5;font-size:12px;line-height:1.6;text-align:center;">
                                This code expires in 10 minutes and can only be used once.
                            </p>
                            <p style="margin:0;color:#888291;font-size:12px;line-height:1.6;text-align:center;">
                                If you didn’t request a password reset, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;color:#777181;font-size:11px;text-align:center;">Oversmoke Vlounge</p>
            </td>
        </tr>
    </table>
</body>
</html>
