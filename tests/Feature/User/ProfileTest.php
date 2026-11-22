<?php

namespace Tests\Feature\User;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0912 345 6789',
            'address' => '123 Example Street, Quezon City',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '09123456789',
            'address' => '123 Example Street, Quezon City',
        ]);
    }

    public function test_customer_profile_updates_only_customer_when_admin_and_customer_are_signed_in(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'is_admin' => true,
        ]);
        $customer = User::factory()->create([
            'name' => 'Customer Name',
        ]);

        Auth::guard('admin')->login($admin);
        Auth::guard('web')->login($customer);

        $this->put(route('profile.update'), [
            'name' => 'Updated Customer',
            'email' => $customer->email,
            'phone' => '0912 345 6789',
            'address' => '123 Example Street, Quezon City',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Store Admin',
        ]);
        $this->assertSame('Store Admin', Auth::guard('admin')->user()->name);
    }

    public function test_stale_admin_customer_session_cannot_change_admin_profile(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'is_admin' => true,
        ]);

        Auth::guard('admin')->login($admin);
        Auth::guard('web')->login($admin);

        $this->put(route('profile.update'), [
            'name' => 'Changed Through Customer Profile',
            'email' => $admin->email,
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Store Admin',
        ]);
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_customer_can_sign_in_through_the_customer_login(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'customer-password',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'customer@example.com',
                'password' => 'customer-password',
            ])
            ->assertRedirect('/welcome');

        $this->assertAuthenticatedAs($user);
    }

    public function test_home_header_shows_the_customers_first_name(): void
    {
        $user = User::factory()->create(['name' => 'Dwayne Dee']);
        Product::create([
            'name' => 'Cloud Berry',
            'brand' => 'Vape Co',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 12,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertOk()
            ->assertSee('Hi, Dwayne')
            ->assertSee('account-notification-toggle', false)
            ->assertSee('data-notification-toggle', false)
            ->assertSee('Refine products')
            ->assertSee('Categories')
            ->assertSee('Price Range')
            ->assertSee('Cloud Berry')
            ->assertSee('Vape Co');
    }

    public function test_notification_preference_is_in_the_account_menu(): void
    {
        $user = User::factory()->create();
        $html = $this->actingAs($user)->get('/home')->assertOk()->getContent();
        $accountMenuStart = strpos($html, 'id="accountMenu"');
        $accountMenuEnd = strpos($html, '</div>', $accountMenuStart);
        $togglePosition = strpos($html, 'class="account-notification-toggle"');
        $notificationMenuStart = strpos($html, 'id="userNotificationMenu"');
        $notificationMenuEnd = strpos($html, '</section>', $notificationMenuStart);

        $this->assertNotFalse($accountMenuStart);
        $this->assertNotFalse($accountMenuEnd);
        $this->assertNotFalse($togglePosition);
        $this->assertNotFalse($notificationMenuStart);
        $this->assertNotFalse($notificationMenuEnd);
        $this->assertGreaterThan($accountMenuStart, $togglePosition);
        $this->assertLessThan($accountMenuEnd, $togglePosition);
        $this->assertFalse($togglePosition > $notificationMenuStart && $togglePosition < $notificationMenuEnd);
    }

    public function test_inactive_products_are_hidden_from_the_customer_catalog(): void
    {
        $user = User::factory()->create();
        Product::create([
            'name' => 'Visible Product',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Hidden Product',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertOk()
            ->assertSee('Visible Product')
            ->assertDontSee('Hidden Product');
    }

    public function test_customer_product_details_use_admin_saved_product_data(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Berry Cloud',
            'brand' => 'Vape Co',
            'category' => 'E-liquids',
            'description' => 'Fresh berry flavor with a cool finish.',
            'price' => 450,
            'stock' => 8,
            'image_path' => 'products/berry-cloud.png',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Berry Cloud')
            ->assertSee('Fresh berry flavor with a cool finish.')
            ->assertSee(asset('storage/products/berry-cloud.png'), false);
    }

    public function test_account_menu_and_cart_navigation_are_available_on_shop_pages(): void
    {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Test Product',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);

        foreach (['/cart', '/orders', '/checkout', '/profile/edit', route('products.show', $product)] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertOk()
                ->assertSee('account-button', false)
                ->assertSee('accountMenu', false)
                ->assertSee('aria-label="Cart"', false)
                ->assertDontSee('>Cart</a>', false);
        }
    }

    public function test_checkout_delivery_details_update_the_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson(route('profile.delivery.update'), [
            'name' => 'Delivery Name',
            'phone' => '09123456789',
            'street' => '123 Example Street',
            'barangay' => 'San Isidro',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1234',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Delivery details saved to your profile.']);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Delivery Name',
            'phone' => '09123456789',
            'address' => '123 Example Street, San Isidro, Makati, Metro Manila, 1234',
            'street' => '123 Example Street',
            'barangay' => 'San Isidro',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1234',
        ]);
    }

    public function test_checkout_and_profile_show_separate_saved_address_fields(): void
    {
        $user = User::factory()->create([
            'street' => '123 Example Street',
            'barangay' => 'San Isidro',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1234',
        ]);

        foreach ([route('checkout'), route('profile.edit')] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertOk()
                ->assertSee('name="street"', false)
                ->assertSee('name="barangay"', false)
                ->assertSee('name="city"', false)
                ->assertSee('name="province"', false)
                ->assertSee('name="postal_code"', false)
                ->assertSee('pattern="09[0-9]{9}"', false)
                ->assertSee('pattern="[0-9]{4}"', false);
        }
    }

    public function test_delivery_details_reject_invalid_phone_and_postal_code(): void
    {
        $user = User::factory()->create();
        $payload = [
            'name' => 'Delivery Name',
            'phone' => '0912345678',
            'street' => '123 Example Street',
            'barangay' => 'San Isidro',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'postal_code' => '1234',
        ];

        $this->actingAs($user)
            ->putJson(route('profile.delivery.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $payload['phone'] = '09123456789';
        $payload['postal_code'] = '123';

        $this->actingAs($user)
            ->putJson(route('profile.delivery.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('postal_code');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('current_password');
    }
}
