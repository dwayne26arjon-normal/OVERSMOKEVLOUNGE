<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_is_available(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Admin portal')
            ->assertSee(route('admin.register'))
            ->assertSee(route('admin.password.request'))
            ->assertSee(route('admin.login.store'), false);
    }

    public function test_owner_can_create_the_first_admin_account(): void
    {
        $this->get(route('admin.register'))
            ->assertOk()
            ->assertSee('Create admin account');

        $this->post(route('admin.register.store'), [
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'is_admin' => true,
        ]);
        $this->assertAuthenticated('admin');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_registration_closes_after_first_admin_is_created(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $this->get(route('admin.register'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status');

        $this->post(route('admin.register.store'), [
            'name' => 'Another Owner',
            'email' => 'another@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('users', ['email' => 'another@example.com']);
    }

    public function test_admin_can_sign_in_and_open_the_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $admin->is_admin = true;
        $admin->save();

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Total products')
            ->assertSee('adminAccountMenu', false)
            ->assertSee(route('admin.logout'), false)
            ->assertSee('Sign out')
            ->assertSee('id="adminLogoutConfirmDialog"', false)
            ->assertSee('Sign out of the admin portal?')
            ->assertSee('Stay signed in');
    }

    public function test_staff_can_sign_in_through_the_admin_portal(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);
        $staff->is_staff = true;
        $staff->save();

        $this->post(route('admin.login.store'), [
            'email' => 'staff@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Store staff');
    }

    public function test_customer_cannot_sign_in_through_the_admin_login(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'email' => 'customer@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_cannot_sign_in_through_the_customer_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $admin->is_admin = true;
        $admin->save();

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_customer_cannot_open_the_admin_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_staff_cannot_sign_in_through_the_customer_login(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);
        $staff->is_staff = true;
        $staff->save();

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'staff@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_admin_and_customer_sessions_are_independent(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/welcome');

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/home')->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest('web');
        $this->get(route('admin.dashboard'))->assertOk();

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/welcome');
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->get('/home')->assertOk();
    }

    public function test_guest_is_sent_to_the_admin_login_before_dashboard_access(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
