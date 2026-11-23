<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminPasswordResetCode;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AdminPasswordResetController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 10;

    public function showLinkRequestForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = Str::lower($request->string('email')->trim()->toString());
        $admin = $this->adminForEmail($email);

        if ($admin) {
            $existingCode = DB::table('password_reset_tokens')->where('email', $admin->email)->first();
            if ($existingCode && now()->diffInSeconds(Carbon::parse($existingCode->created_at)) < 60) {
                return back()->with('status', 'If an owner or staff account exists for that email, a reset code has been sent. Wait a minute before requesting another code.');
            }

            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $admin->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                $admin->notify(new AdminPasswordResetCode($code));
            } catch (TransportExceptionInterface $exception) {
                DB::table('password_reset_tokens')->where('email', $admin->email)->delete();
                Log::error('Admin password reset email delivery failed.', [
                    'exception' => $exception::class,
                    'reason' => $exception->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'email' => ['We could not deliver the reset email. Please check the mail settings and try again.'],
                ]);
            }
        }

        $request->session()->forget('admin_password_reset_verified');

        return redirect()
            ->route('admin.password.code', ['email' => $email])
            ->with('status', 'If an owner or staff account exists for that email address, a six-digit reset code has been sent.');
    }

    public function showCodeForm(Request $request): View
    {
        return view('admin.auth.verify-reset-code', [
            'email' => $request->query('email', ''),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = Str::lower(trim($data['email']));
        $rateLimitKey = $this->rateLimitKey($request, $email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Request a new code and try again.'],
            ]);
        }

        $admin = $this->adminForEmail($email);
        $resetRecord = $admin
            ? DB::table('password_reset_tokens')->where('email', $admin->email)->first()
            : null;

        $isExpired = ! $resetRecord
            || ! $resetRecord->created_at
            || Carbon::parse($resetRecord->created_at)->lte(now()->subMinutes(self::CODE_LIFETIME_MINUTES));

        if (! $admin || $isExpired || ! Hash::check($data['code'], $resetRecord->token)) {
            RateLimiter::hit($rateLimitKey, 600);

            if (RateLimiter::tooManyAttempts($rateLimitKey, 5) && $admin) {
                DB::table('password_reset_tokens')->where('email', $admin->email)->delete();
            }

            throw ValidationException::withMessages([
                'code' => ['That code is invalid or expired. Request a new code and try again.'],
            ]);
        }

        DB::table('password_reset_tokens')->where('email', $admin->email)->delete();
        RateLimiter::clear($rateLimitKey);
        $request->session()->put('admin_password_reset_verified', [
            'email' => $admin->email,
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES)->timestamp,
        ]);

        return redirect()->route('admin.password.reset');
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $verifiedReset = $request->session()->get('admin_password_reset_verified');

        if (! $this->hasValidVerifiedReset($verifiedReset)) {
            $request->session()->forget('admin_password_reset_verified');

            return redirect()->route('admin.password.request')
                ->withErrors(['email' => 'Verify your reset code before choosing a new password.']);
        }

        return view('admin.auth.reset-password', [
            'email' => $verifiedReset['email'],
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verifiedReset = $request->session()->get('admin_password_reset_verified');

        if (! $this->hasValidVerifiedReset($verifiedReset)) {
            $request->session()->forget('admin_password_reset_verified');

            return redirect()->route('admin.password.request')
                ->withErrors(['email' => 'Your reset session expired. Request a new code.']);
        }

        $admin = $this->adminForEmail($verifiedReset['email']);

        if (! $admin) {
            $request->session()->forget('admin_password_reset_verified');

            return redirect()->route('admin.password.request')
                ->withErrors(['email' => 'Store account not found. Request a new code.']);
        }

        $admin->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($admin));
        $request->session()->forget('admin_password_reset_verified');

        return redirect()
            ->route('admin.login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }

    private function adminForEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($query) {
                $query->where('is_admin', true)
                    ->orWhere('is_staff', true);
            })
            ->first();
    }

    private function hasValidVerifiedReset(mixed $verifiedReset): bool
    {
        return is_array($verifiedReset)
            && isset($verifiedReset['email'], $verifiedReset['expires_at'])
            && is_string($verifiedReset['email'])
            && is_int($verifiedReset['expires_at'])
            && $verifiedReset['expires_at'] > now()->timestamp;
    }

    private function rateLimitKey(Request $request, string $email): string
    {
        return 'admin-password-reset-code:'.hash('sha256', $email.'|'.$request->ip());
    }
}
