<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\CustomerPasswordResetCode;
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

class PasswordResetController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 10;

    public function showLinkRequestForm(): View
    {
        return view('user.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower($request->string('email')->trim()->toString());
        $customer = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($query) {
                $query->where('is_admin', false)
                    ->orWhereNull('is_admin');
            })
            ->where('is_staff', false)
            ->first();

        if ($customer) {
            $existingCode = DB::table('password_reset_tokens')->where('email', $customer->email)->first();
            if ($existingCode && now()->diffInSeconds(Carbon::parse($existingCode->created_at)) < 60) {
                return redirect()
                    ->route('password.code', ['email' => $email])
                    ->with('status', __('If an account exists for that email address, a reset code has been sent. Wait a minute before requesting another code.'));
            }

            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $customer->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            try {
                $customer->notify(new CustomerPasswordResetCode($code));
            } catch (TransportExceptionInterface $exception) {
                DB::table('password_reset_tokens')->where('email', $customer->email)->delete();
                Log::error('Customer password reset email delivery failed.', [
                    'exception' => $exception::class,
                ]);

                throw ValidationException::withMessages([
                    'email' => ['We could not deliver the reset email. Please check the mail settings and try again.'],
                ]);
            }
        }

        $request->session()->forget('customer_password_reset_verified');

        return redirect()
            ->route('password.code', ['email' => $email])
            ->with('status', __('If a customer account exists for that email address, a six-digit reset code has been sent.'));
    }

    public function showCodeForm(Request $request): View
    {
        return view('user.auth.verify-reset-code', [
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
                'code' => ['Too many incorrect attempts. Request a new reset code and try again.'],
            ]);
        }

        $customer = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($query) {
                $query->where('is_admin', false)
                    ->orWhereNull('is_admin');
            })
            ->where('is_staff', false)
            ->first();
        $resetRecord = $customer
            ? DB::table('password_reset_tokens')->where('email', $customer->email)->first()
            : null;

        $isExpired = ! $resetRecord
            || ! $resetRecord->created_at
            || Carbon::parse($resetRecord->created_at)->lte(now()->subMinutes(self::CODE_LIFETIME_MINUTES));

        if (! $customer || $isExpired || ! Hash::check($data['code'], $resetRecord->token)) {
            RateLimiter::hit($rateLimitKey, 600);

            if (RateLimiter::tooManyAttempts($rateLimitKey, 5) && $customer) {
                DB::table('password_reset_tokens')->where('email', $customer->email)->delete();
            }

            throw ValidationException::withMessages([
                'code' => ['That code is invalid or expired. Request a new code and try again.'],
            ]);
        }

        DB::table('password_reset_tokens')->where('email', $customer->email)->delete();
        RateLimiter::clear($rateLimitKey);
        $request->session()->put('customer_password_reset_verified', [
            'email' => $customer->email,
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES)->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $verifiedReset = $request->session()->get('customer_password_reset_verified');

        if (! $this->hasValidVerifiedReset($verifiedReset)) {
            $request->session()->forget('customer_password_reset_verified');

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Verify your reset code before choosing a new password.']);
        }

        return view('user.auth.reset-password', [
            'email' => $verifiedReset['email'],
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verifiedReset = $request->session()->get('customer_password_reset_verified');

        if (! $this->hasValidVerifiedReset($verifiedReset)) {
            $request->session()->forget('customer_password_reset_verified');

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Your reset session expired. Request a new code.']);
        }

        $customer = User::query()
            ->where('email', $verifiedReset['email'])
            ->where(function ($query) {
                $query->where('is_admin', false)
                    ->orWhereNull('is_admin');
            })
            ->where('is_staff', false)
            ->first();

        if (! $customer) {
            $request->session()->forget('customer_password_reset_verified');

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Customer account not found. Request a new code.']);
        }

        $customer->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($customer));
        $request->session()->forget('customer_password_reset_verified');

        return redirect()
            ->route('login')
            ->with('status', __('Your password has been reset. You can now sign in.'));
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
        return 'customer-password-reset-code:'.hash('sha256', $email.'|'.$request->ip());
    }
}
