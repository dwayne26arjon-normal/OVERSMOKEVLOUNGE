<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Notifications\AdminPasswordResetCode;
use App\Notifications\CustomerPasswordResetCode;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_a_password_reset_email(): void
    {
        Notification::fake();
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Account recovery');

        $this->post(route('password.email'), [
            'email' => 'customer@example.com',
        ])->assertRedirect(route('password.code', ['email' => 'customer@example.com']))
            ->assertSessionHas('status');

        Notification::assertSentTo($customer, CustomerPasswordResetCode::class);
    }

    public function test_customer_and_admin_reset_emails_include_the_store_logo(): void
    {
        foreach ([
            new CustomerPasswordResetCode('123456'),
            new AdminPasswordResetCode('654321'),
        ] as $notification) {
            $message = $notification->toMail(new User);
            $this->assertSame('emails.password-reset-code', $message->view['html']);
            $this->assertSame('emails.text.password-reset-code', $message->view['text']);

            $html = view($message->view['html'], $message->viewData)->render();
            $this->assertStringContainsString(asset('images/oversmokev-logo.png'), $html);
        }
    }

    public function test_customer_repeating_a_reset_request_is_redirected_to_code_entry(): void
    {
        Notification::fake();
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => $customer->email,
        ])->assertRedirect(route('password.code', ['email' => $customer->email]));

        $this->post(route('password.email'), [
            'email' => $customer->email,
        ])->assertRedirect(route('password.code', ['email' => $customer->email]))
            ->assertSessionHas('status', 'If an account exists for that email address, a reset code has been sent. Wait a minute before requesting another code.');

        $this->get(route('password.code', ['email' => $customer->email]))
            ->assertOk()
            ->assertSee('Enter your verification code')
            ->assertSee('name="code"', false);

        Notification::assertSentTo($customer, CustomerPasswordResetCode::class);
    }

    public function test_unknown_email_gets_generic_password_reset_response(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ])->assertRedirect(route('password.code', ['email' => 'unknown@example.com']))
            ->assertSessionHas('status', 'If a customer account exists for that email address, a six-digit reset code has been sent.');

        Notification::assertNothingSent();
    }

    public function test_admin_account_cannot_request_a_customer_password_reset(): void
    {
        Notification::fake();
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->is_admin = true;
        $admin->save();

        $this->post(route('password.email'), [
            'email' => 'admin@example.com',
        ])->assertRedirect(route('password.code', ['email' => 'admin@example.com']))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_smtp_failure_returns_a_friendly_error_instead_of_a_server_error(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('SMTP authentication failed.'));
        app()->instance(Dispatcher::class, $dispatcher);
        Log::shouldReceive('error')
            ->once()
            ->with('Customer password reset email delivery failed.', [
                'exception' => TransportException::class,
            ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $customer->email,
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'email' => 'We could not deliver the reset email. Please check the mail settings and try again.',
            ]);
    }

    public function test_customer_can_reset_password_using_a_valid_email_code(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'old-password-123',
        ]);
        Notification::fake();

        $this->post(route('password.email'), ['email' => $customer->email])
            ->assertRedirect(route('password.code', ['email' => $customer->email]));

        $code = null;
        Notification::assertSentTo(
            $customer,
            CustomerPasswordResetCode::class,
            function (CustomerPasswordResetCode $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));

        $this->post(route('password.code.verify'), [
            'email' => $customer->email,
            'code' => $code,
        ])->assertRedirect(route('password.reset'));

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-controls="password"', false)
            ->assertSee('aria-controls="password_confirmation"', false);

        $this->post(route('password.store'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $customer->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $customer->email]);
    }

    public function test_invalid_reset_code_is_rejected(): void
    {
        $customer = User::factory()->create();

        $this->post(route('password.code.verify'), [
            'email' => $customer->email,
            'code' => '123456',
        ])->assertSessionHasErrors('code');

        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));
    }

    public function test_reset_code_can_only_be_used_once(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['email' => 'customer@example.com']);

        $this->post(route('password.email'), ['email' => $customer->email]);
        $code = null;
        Notification::assertSentTo(
            $customer,
            CustomerPasswordResetCode::class,
            function (CustomerPasswordResetCode $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->post(route('password.code.verify'), [
            'email' => $customer->email,
            'code' => $code,
        ])->assertRedirect(route('password.reset'));

        $this->post(route('password.code.verify'), [
            'email' => $customer->email,
            'code' => $code,
        ])->assertSessionHasErrors('code');
    }

    public function test_reset_code_expires_after_ten_minutes(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['email' => 'customer@example.com']);

        $this->post(route('password.email'), ['email' => $customer->email]);
        $code = null;
        Notification::assertSentTo(
            $customer,
            CustomerPasswordResetCode::class,
            function (CustomerPasswordResetCode $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        DB::table('password_reset_tokens')
            ->where('email', $customer->email)
            ->update(['created_at' => now()->subMinutes(11)]);

        $this->post(route('password.code.verify'), [
            'email' => $customer->email,
            'code' => $code,
        ])->assertSessionHasErrors('code');
    }
}
