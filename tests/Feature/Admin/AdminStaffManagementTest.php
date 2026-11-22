<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_remove_staff_accounts(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner, 'admin');

        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Staff accounts')
            ->assertSee('class="nav-icon nav-icon-svg"', false)
            ->assertSee('<circle cx="12" cy="8" r="3.5"/>', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('.password-toggle:focus-visible { outline: none; }', false)
            ->assertSee('id="staffRemoveDialog"', false);

        $this->post(route('admin.staff.store'), [
            'name' => 'Store Staff',
            'email' => 'staff@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('admin.staff.index'));

        $staff = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertFalse($staff->is_admin);
        $this->assertTrue($staff->is_staff);

        $this->delete(route('admin.staff.destroy', $staff))
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('status', 'Staff account removed.');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);

        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Staff account removed.')
            ->assertSee('Remove staff access?')
            ->assertSee('data-admin-flash', false)
            ->assertSee('data-dismiss-flash', false);
    }

    public function test_owner_can_create_another_admin_account(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner, 'admin');

        $this->post(route('admin.staff.admin.store'), [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('admin.staff.index'));

        $admin = User::query()->where('email', 'second-admin@example.com')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertFalse($admin->is_staff);

        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Second Admin');
    }

    public function test_staff_can_access_store_work_areas_but_not_staff_management(): void
    {
        $staff = User::factory()->create();
        $staff->is_staff = true;
        $staff->save();
        $this->actingAs($staff, 'admin');

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.inventory.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.sales.export', ['range' => 'month']))->assertOk();
        $this->get(route('admin.staff.index'))->assertForbidden();

        $this->post(route('admin.products.store'), [
            'name' => 'Staff Added Product',
            'brand' => 'Store Brand',
            'category' => 'E-liquids',
            'description' => 'Added by staff.',
            'price' => '450',
            'stock' => 5,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Staff Added Product']);
    }

    public function test_customer_cannot_create_staff_accounts(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer, 'admin');

        $this->post(route('admin.staff.store'), [
            'name' => 'Unauthorized',
            'email' => 'unauthorized@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.com']);
    }
}
