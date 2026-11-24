<?php

namespace Tests\Feature\User;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_home_and_product_details(): void
    {
        $product = Product::create([
            'name' => 'Public Product',
            'brand' => 'Vape Co',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Public Product')
            ->assertSee('/go/login')
            ->assertSee('/go/register');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Public Product')
            ->assertSee('data-authenticated="false"', false)
            ->assertSee('id="authDialog"', false)
            ->assertSee('/go/login')
            ->assertSee('/go/register');
    }

    public function test_featured_products_appear_first_and_are_marked_on_home(): void
    {
        Product::create([
            'name' => 'Regular Product',
            'category' => 'Devices',
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Featured Product',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
            'is_featured' => true,
        ]);
        Product::create([
            'name' => 'Second Featured Product',
            'category' => 'Devices',
            'price' => 1400,
            'stock' => 5,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Featured Product', 'Second Featured Product', 'Regular Product'])
            ->assertSee('class="banner-featured" aria-label="Featured products"', false)
            ->assertSee('data-featured-next', false)
            ->assertSee('data-featured-dot="1"', false)
            ->assertSee('data-featured-toggle', false)
            ->assertSee(route('products.show', Product::where('name', 'Featured Product')->first()), false)
            ->assertSee(route('products.show', Product::where('name', 'Regular Product')->first()), false)
            ->assertSee('featured-label">Featured', false);
    }

    public function test_home_carousel_includes_all_active_featured_products(): void
    {
        foreach (range(1, 12) as $number) {
            Product::create([
                'name' => "Featured Product {$number}",
                'category' => 'Devices',
                'price' => 1000 + $number * 100,
                'stock' => 5,
                'is_active' => true,
                'is_featured' => true,
            ]);
        }

        Product::create([
            'name' => 'Inactive Featured Product',
            'category' => 'Devices',
            'price' => 2000,
            'stock' => 5,
            'is_active' => false,
            'is_featured' => true,
        ]);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-featured-dot="11"', false)
            ->assertDontSee('Inactive Featured Product');

        $this->assertSame(12, substr_count($response->getContent(), 'data-featured-slide'));
    }

    public function test_home_feature_cards_use_the_supplied_icon_images(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('images/features/authenticity.png'), false)
            ->assertSee(asset('images/features/delivery.png'), false)
            ->assertSee(asset('images/features/price.png'), false)
            ->assertDontSee('💨')
            ->assertDontSee('🚀')
            ->assertDontSee('💜');
    }

    public function test_product_details_provides_search_for_many_options(): void
    {
        $product = Product::create([
            'name' => 'Colorful Pod',
            'category' => 'Pod System',
            'price' => 1200,
            'stock' => 9,
            'is_active' => true,
        ]);
        foreach (range(1, 9) as $index) {
            $product->variants()->create([
                'name' => 'Color '.$index,
                'stock' => 1,
            ]);
        }

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('id="optionSearch"', false)
            ->assertSee('Search colors')
            ->assertSee('Color 9')
            ->assertSee('1 available');
    }

    public function test_out_of_stock_options_remain_selectable_for_preview_but_cannot_be_purchased(): void
    {
        $product = Product::create([
            'name' => 'Sold Out Pod',
            'category' => 'Pod System',
            'price' => 1200,
            'stock' => 0,
            'is_active' => true,
        ]);
        $product->variants()->create([
            'name' => 'Blue',
            'stock' => 0,
            'image_path' => 'products/variants/blue.png',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('class="color-option is-out-of-stock selected"', false)
            ->assertSee('data-option="Blue" data-stock="0"', false)
            ->assertSee('id="addToCart" disabled', false)
            ->assertSee('id="buyNow" disabled', false);
    }

    public function test_product_details_provides_an_accessible_cart_success_popup(): void
    {
        $product = Product::create([
            'name' => 'Cart Toast Product',
            'category' => 'Devices',
            'price' => 1200,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('id="cartSuccessToast"', false)
            ->assertSee('role="status" aria-live="polite"', false)
            ->assertSee('Successfully added to cart.');
    }

    public function test_purchase_routes_require_login_and_return_to_requested_page(): void
    {
        $this->get(route('checkout'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => bcrypt('customer-password'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'customer-password',
        ])->assertRedirect(route('checkout'));
    }
}
