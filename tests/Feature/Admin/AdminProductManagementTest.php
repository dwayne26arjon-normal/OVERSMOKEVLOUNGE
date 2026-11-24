<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_products_and_inventory(): void
    {
        $admin = $this->createAdmin();
        Product::create([
            'name' => 'Pod Device',
            'brand' => 'Vape Co',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pod Device')
            ->assertSee('Stock attention');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Pod Device')
            ->assertSee('PRODUCT NAME');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index', ['stock' => 'low']))
            ->assertOk()
            ->assertSee('Pod Device')
            ->assertSee('Low stock')
            ->assertSee('id="inventoryDeleteDialog"', false)
            ->assertSee('data-inventory-delete-form', false)
            ->assertSee('.delete-confirm-dialog::backdrop', false)
            ->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_product_and_inventory_lists_use_scroll_pagination_with_page_totals(): void
    {
        $admin = $this->createAdmin();
        foreach (range(1, 11) as $number) {
            Product::create([
                'name' => "Product {$number}",
                'category' => 'Devices',
                'price' => 1000,
                'stock' => 5,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Page 1 of 2')
            ->assertSee('data-pagination-window', false)
            ->assertSee('rel="next"', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Page 2 of 2')
            ->assertSee('rel="prev"', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Page 1 of 2')
            ->assertSee('data-pagination-window', false)
            ->assertSee('rel="next"', false);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Page 2 of 2')
            ->assertSee('rel="prev"', false);
    }

    public function test_admin_inventory_shows_product_counts_and_filters_by_category_and_stock(): void
    {
        $admin = $this->createAdmin();
        Product::create([
            'name' => 'Berry Cloud',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 9,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Mint Cloud',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 2,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Pod Device',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 0,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Nic Salt',
            'category' => 'E-liquids',
            'price' => 500,
            'stock' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index', [
                'category' => 'E-liquids',
                'sort' => 'low',
            ]))
            ->assertOk()
            ->assertSee('TOTAL PRODUCTS')
            ->assertSee('IN STOCK')
            ->assertSee('LOW STOCKS')
            ->assertSee('OUT OF STOCK')
            ->assertSee('id="inventory-stock"', false)
            ->assertSee('>Apply</button>', false)
            ->assertSeeInOrder(['Mint Cloud', 'Berry Cloud'])
            ->assertDontSee('Pod Device');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index', ['stock' => 'empty']))
            ->assertOk()
            ->assertSee('Pod Device')
            ->assertDontSee('Berry Cloud')
            ->assertDontSee('Nic Salt');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.inventory.index', ['stock' => 'in_stock']))
            ->assertOk()
            ->assertSee('Nic Salt')
            ->assertDontSee('Mint Cloud')
            ->assertDontSee('Pod Device');
    }

    public function test_admin_product_catalog_shows_classifications_and_filters_by_category(): void
    {
        $admin = $this->createAdmin();
        Product::create([
            'name' => 'Berry Cloud',
            'category' => 'E-liquids',
            'price' => 450,
            'stock' => 5,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Pod Device',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 30,
            'is_active' => false,
        ]);
        Product::create([
            'name' => 'Empty Pod',
            'category' => 'Devices',
            'price' => 900,
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['category' => 'E-liquids']))
            ->assertOk()
            ->assertSee('PRODUCT NAME')
            ->assertSee('CATEGORY')
            ->assertSee('STOCK')
            ->assertSee('STATUS')
            ->assertSee('Low stock')
            ->assertSee('E-liquids')
            ->assertDontSee('Pod Device');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Out of stock')
            ->assertSee('In stock')
            ->assertSee('Hidden');
    }

    public function test_product_form_offers_standard_and_custom_categories(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Juices')
            ->assertSee('Pod System')
            ->assertSee('Disposable Vapes')
            ->assertSee('Custom category')
            ->assertSee('Enter custom category');
    }

    public function test_product_created_by_admin_appears_in_customer_store(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Admin Added Berry',
                'brand' => 'Vape Co',
                'category' => 'E-liquids',
                'description' => 'Berry flavor.',
                'price' => '450',
                'stock' => 20,
                'is_active' => '1',
                'is_featured' => '1',
                'is_hot' => '1',
                'is_best_seller' => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Admin Added Berry')->firstOrFail();
        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->is_hot);
        $this->assertTrue($product->is_best_seller);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Featured product')
            ->assertSee('Hot item')
            ->assertSee('Best seller')
            ->assertSee('name="is_featured"', false)
            ->assertSee('name="is_hot"', false)
            ->assertSee('name="is_best_seller"', false)
            ->assertSee('checked', false);

        $this->actingAs($customer, 'web')
            ->get('/home')
            ->assertOk()
            ->assertSee('Admin Added Berry')
            ->assertSee('Vape Co')
            ->assertSee('20 available')
            ->assertSee('Best Seller')
            ->assertSee('>Hot</span>', false)
            ->assertSee('class="featured-label">Featured', false);
    }

    public function test_admin_can_create_update_and_delete_products(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.products.store'), [
            'name' => 'Cloud Pod',
            'brand' => 'Vape Co',
            'category' => 'Pod Systems',
            'description' => 'A sample product description.',
            'price' => '1499.50',
            'stock' => 25,
            'is_active' => '1',
            'is_featured' => '1',
            'image' => UploadedFile::fake()->createWithContent(
                'cloud-pod.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Cloud Pod')->firstOrFail();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
            'is_featured' => true,
            'stock' => 25,
        ]);
        Storage::disk('public')->assertExists($product->image_path);

        $this->put(route('admin.products.update', $product), [
            'name' => 'Cloud Pod Plus',
            'brand' => 'Vape Co',
            'category' => 'Pod Systems',
            'description' => 'Updated product details.',
            'price' => '1599.00',
            'stock' => 12,
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Cloud Pod Plus',
            'is_featured' => false,
            'stock' => 12,
        ]);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('id="deleteConfirmDialog"', false)
            ->assertSee('Delete product?');

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($product->image_path);
        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('data-admin-flash', false)
            ->assertSee('Product deleted successfully.')
            ->assertSee('data-dismiss-flash', false);
    }

    public function test_admin_can_upload_promotional_artwork_and_configure_color_stock(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Color Pod',
                'category' => 'Pod Systems',
                'price' => 1200,
                'stock' => 0,
                'is_active' => '1',
                'is_featured' => '1',
                'image' => UploadedFile::fake()->createWithContent(
                    'product.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=')
                ),
                'featured_image' => UploadedFile::fake()->createWithContent(
                    'promo.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=')
                ),
                'variants' => [
                    [
                        'name' => 'Midnight Black',
                        'stock' => 4,
                        'image' => UploadedFile::fake()->createWithContent(
                            'black.png',
                            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=')
                        ),
                    ],
                    ['name' => 'Ice Blue', 'stock' => 3],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Color Pod')->with('variants')->firstOrFail();
        $blackVariant = $product->variants->firstWhere('name', 'Midnight Black');
        $this->assertSame(7, $product->stock);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Midnight Black',
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Ice Blue',
            'stock' => 3,
        ]);
        $this->assertNotNull($blackVariant->image_path);
        Storage::disk('public')->assertExists($blackVariant->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        Storage::disk('public')->assertExists($product->featured_image_path);

        $this->actingAs($admin, 'admin')
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Color')
            ->assertSee('Midnight Black')
            ->assertSee('Ice Blue')
            ->assertSee('class="color-option-image"', false)
            ->assertSee(asset('storage/' . $blackVariant->image_path), false);

        $this->actingAs($admin, 'admin')
            ->get(route('home'))
            ->assertOk()
            ->assertSee(asset('storage/' . $product->featured_image_path), false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Berry Juice',
                'category' => 'Juices',
                'price' => 450,
                'stock' => 0,
                'is_active' => '1',
                'variants' => [
                    ['name' => 'Blueberry Ice', 'stock' => 6],
                    ['name' => 'Cool Mint', 'stock' => 2],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $juice = Product::where('name', 'Berry Juice')->with('variants')->firstOrFail();
        $this->assertSame(8, $juice->stock);
        $this->actingAs($admin, 'admin')
            ->get(route('products.show', $juice))
            ->assertOk()
            ->assertSee('Flavor')
            ->assertSee('Blueberry Ice')
            ->assertSee('Cool Mint');
    }

    public function test_variant_images_survive_edits_and_are_removed_with_the_product(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l3sAAAAASUVORK5CYII=');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => 'Berry Juice',
                'category' => 'Juices',
                'price' => 450,
                'stock' => 0,
                'is_active' => '1',
                'variants' => [
                    [
                        'name' => 'Berry Ice',
                        'stock' => 3,
                        'image' => UploadedFile::fake()->createWithContent('berry.png', $imageContent),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', 'Berry Juice')->with('variants')->firstOrFail();
        $firstImagePath = $product->variants->first()->image_path;
        $this->assertNotNull($firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('variants[0][image]', false)
            ->assertSee('Overall stock')
            ->assertSee(asset('storage/' . $firstImagePath), false);

        $this->put(route('admin.products.update', $product), [
            'name' => 'Berry Juice',
            'category' => 'Juices',
            'price' => 450,
            'stock' => 0,
            'is_active' => '1',
            'variants' => [['name' => 'Berry Ice', 'stock' => 3]],
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame($firstImagePath, $product->fresh()->variants->first()->image_path);
        Storage::disk('public')->assertExists($firstImagePath);

        $this->put(route('admin.products.update', $product), [
            'name' => 'Berry Juice',
            'category' => 'Juices',
            'price' => 450,
            'stock' => 0,
            'is_active' => '1',
            'variants' => [
                [
                    'name' => 'Berry Ice',
                    'stock' => 3,
                    'image' => UploadedFile::fake()->createWithContent('berry-new.png', $imageContent),
                ],
            ],
        ])->assertRedirect(route('admin.products.index'));

        $updatedImagePath = $product->fresh()->variants->first()->image_path;
        $this->assertNotSame($firstImagePath, $updatedImagePath);
        Storage::disk('public')->assertMissing($firstImagePath);
        Storage::disk('public')->assertExists($updatedImagePath);

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));
        Storage::disk('public')->assertMissing($updatedImagePath);
    }

    public function test_customer_cannot_manage_admin_products(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.products.store'), [
                'name' => 'Unauthorized product',
                'category' => 'Other',
                'price' => 1,
                'stock' => 1,
            ])
            ->assertForbidden();
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }
}
