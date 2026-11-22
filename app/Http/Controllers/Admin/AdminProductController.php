<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function dashboard(Request $request): View
    {
        $today = now()->startOfDay();
        $period = $request->query('sales_period', 'day');
        $formats = [
            'day' => 'Y-m-d',
            'month' => 'Y-m',
            'year' => 'Y',
        ];
        $validPeriod = is_string($period) && array_key_exists($period, $formats);
        $format = $validPeriod ? $formats[$period] : $formats['day'];
        $currentValues = [
            'day' => now()->format('Y-m-d'),
            'month' => now()->format('Y-m'),
            'year' => now()->format('Y'),
        ];
        $request->merge([
            'sales_period' => $period,
            'sales_value' => $request->query('sales_value', $currentValues[$validPeriod ? $period : 'day']),
        ]);
        $validated = $request->validate([
            'sales_period' => ['required', Rule::in(array_keys($formats))],
            'sales_value' => $period === 'year'
                ? ['required', 'integer', 'between:2000,' . now()->year]
                : ['required', 'date_format:' . $format],
        ]);
        $selectedDate = \Illuminate\Support\Carbon::createFromFormat('!' . $format, $validated['sales_value']);
        $selectedStart = $selectedDate->copy()->startOf($period);
        $selectedEnd = $selectedDate->copy()->endOf($period);
        $selectedSalesQuery = Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$selectedStart, $selectedEnd]);
        $selectedPeriodLabel = match ($period) {
            'month' => $selectedDate->format('F Y'),
            'year' => $selectedDate->format('Y'),
            default => $selectedDate->format('F j, Y'),
        };
        $selectedOrders = (clone $selectedSalesQuery)->get(['created_at', 'total']);
        $selectedSales = (float) $selectedOrders->sum(fn (Order $order) => (float) $order->total);
        $categorySales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$selectedStart, $selectedEnd])
            ->selectRaw("COALESCE(NULLIF(order_items.category, ''), 'Uncategorized') as category_name")
            ->selectRaw('SUM(order_items.quantity * order_items.unit_price) as sales_total')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->groupBy('order_items.category')
            ->orderByDesc('sales_total')
            ->get();
        $categoryPurchases = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$selectedStart, $selectedEnd])
            ->selectRaw("COALESCE(NULLIF(order_items.category, ''), 'Uncategorized') as category_name")
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->groupBy('order_items.category')
            ->orderByDesc('units_sold')
            ->get();
        $totalUnitsSold = (int) $categoryPurchases->sum('units_sold');
        $donutColors = ['#8b6cff', '#42c7b9', '#f4b942', '#f06b8b', '#4b9bf5', '#a8cf5a'];
        $donutOffset = 0;
        $categoryPurchases = $categoryPurchases->map(function ($category, int $index) use (&$donutOffset, $donutColors, $totalUnitsSold): array {
            $units = (int) $category->units_sold;
            $share = $totalUnitsSold > 0 ? $units / $totalUnitsSold * 100 : 0;
            $start = $donutOffset;
            $donutOffset += $share;

            return [
                'name' => $category->category_name,
                'units' => $units,
                'share' => $share,
                'color' => $donutColors[$index % count($donutColors)],
                'start' => $start,
                'end' => $donutOffset,
            ];
        });
        $topPurchasedProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [$selectedStart, $selectedEnd])
            ->select('order_items.product_name', 'order_items.brand')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->groupBy('order_items.product_name', 'order_items.brand')
            ->orderByDesc('units_sold')
            ->orderBy('order_items.product_name')
            ->limit(5)
            ->get();
        $salesChartTitle = match ($period) {
            'month' => 'Sales by week — ' . $selectedDate->format('F Y'),
            'year' => 'Sales by month — ' . $selectedDate->format('Y'),
            default => 'Sales in 4-hour blocks — ' . $selectedDate->format('F j, Y'),
        };
        $salesChartDescription = 'Bar height shows order sales. Hover over a bar for the exact amount and order count.';
        $slotCount = match ($period) {
            'month' => (int) ceil($selectedDate->daysInMonth / 7),
            'year' => 12,
            default => 6,
        };
        $salesBySlot = $selectedOrders->groupBy(function (Order $order) use ($period): int {
            return match ($period) {
                'month' => (int) ceil((int) $order->created_at->format('j') / 7),
                'year' => (int) $order->created_at->format('n'),
                default => (int) floor((int) $order->created_at->format('G') / 4),
            };
        });
        $slotStart = $period === 'day' ? 0 : 1;
        $salesChart = collect(range($slotStart, $slotStart + $slotCount - 1))->map(function (int $slot) use ($period, $selectedDate, $salesBySlot): array {
            $slotOrders = $salesBySlot->get($slot, collect());
            $date = $period === 'year'
                ? $selectedDate->copy()->month($slot)
                : ($period === 'month'
                    ? $selectedDate->copy()->startOfMonth()->addDays(($slot - 1) * 7)
                    : $selectedDate->copy()->startOfDay()->addHours($slot * 4));
            $rangeEnd = match ($period) {
                'month' => min($slot * 7, $selectedDate->daysInMonth),
                default => null,
            };

            return [
                'label' => match ($period) {
                    'month' => 'Week ' . $slot,
                    'year' => $date->format('M'),
                    default => $date->format('g A') . '–' . $date->copy()->addHours(4)->format('g A'),
                },
                'date' => match ($period) {
                    'month' => $date->format('M j') . '–' . $selectedDate->copy()->day($rangeEnd)->format('M j'),
                    'year' => $date->format('F Y'),
                    default => $date->format('M j, g A') . '–' . $date->copy()->addHours(4)->format('g A'),
                },
                'sales' => (float) $slotOrders->sum(fn (Order $order) => (float) $order->total),
                'orders' => $slotOrders->count(),
            ];
        });

        $salesByDay = Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$today->copy()->subDays(6), $today->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as sales_date, SUM(total) as sales_total, COUNT(*) as order_count')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('sales_date');

        $dailySales = collect(range(0, 6))->map(function (int $offset) use ($today, $salesByDay): array {
            $date = $today->copy()->subDays(6 - $offset);
            $daySales = $salesByDay->get($date->toDateString());

            return [
                'label' => $date->format('D'),
                'date' => $date->format('M j'),
                'sales' => (float) ($daySales->sales_total ?? 0),
                'orders' => (int) ($daySales->order_count ?? 0),
            ];
        });
        $nonCancelledOrders = fn () => Order::query()->where('status', '!=', 'cancelled');

        return view('admin.dashboard', [
            'productCount' => Product::count(),
            'inventoryUnits' => Product::sum('stock'),
            'wellStockUnits' => Product::where('stock', '>', 0)->sum('stock'),
            'lowStockCount' => Product::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'outOfStockCount' => Product::where('stock', 0)->count(),
            'inventoryValue' => Product::selectRaw('COALESCE(SUM(price * stock), 0) as total')->value('total'),
            'recentProducts' => Product::latest()->limit(5)->get(),
            'lowStockProducts' => Product::where('stock', '<=', 10)->orderBy('stock')->limit(5)->get(),
            'salesToday' => (clone $nonCancelledOrders())->whereBetween('created_at', [$today, $today->copy()->endOfDay()])->sum('total'),
            'salesThisMonth' => (clone $nonCancelledOrders())->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            'salesThisYear' => (clone $nonCancelledOrders())->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])->sum('total'),
            'grossSalesToDate' => Order::query()->where('status', '!=', 'cancelled')->sum('total'),
            'selectedSales' => $selectedSales,
            'selectedOrderCount' => $selectedOrders->count(),
            'categorySales' => $categorySales,
            'maxCategorySales' => max(1, (float) $categorySales->max('sales_total')),
            'categoryPurchases' => $categoryPurchases,
            'totalUnitsSold' => $totalUnitsSold,
            'topPurchasedProducts' => $topPurchasedProducts,
            'maxProductUnitsSold' => max(1, (int) $topPurchasedProducts->max('units_sold')),
            'selectedPeriodLabel' => $selectedPeriodLabel,
            'salesPeriod' => $period,
            'salesValue' => $validated['sales_value'],
            'salesChart' => $salesChart,
            'salesChartTitle' => $salesChartTitle,
            'salesChartDescription' => $salesChartDescription,
            'maxChartSales' => max(1, (float) $salesChart->max('sales')),
        ]);
    }

    public function products(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Product::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function inventory(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->input('stock') === 'in_stock', fn ($query) => $query->where('stock', '>', 10))
            ->when($request->input('stock') === 'low', fn ($query) => $query->where('stock', '>', 0)->where('stock', '<=', 10))
            ->when($request->input('stock') === 'empty', fn ($query) => $query->where('stock', 0))
            ->when($request->input('sort') === 'low', fn ($query) => $query->orderByRaw('CASE WHEN stock = 0 THEN 1 ELSE 0 END')->orderBy('stock'))
            ->when($request->input('sort') === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->input('sort') === 'stock', fn ($query) => $query->orderByDesc('stock'))
            ->when(! in_array($request->input('sort'), ['low', 'name', 'stock'], true), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventory.index', [
            'products' => $products,
            'productCount' => Product::count(),
            'inStockProductCount' => Product::where('stock', '>', 0)->count(),
            'categories' => Product::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
            'inventoryUnits' => Product::sum('stock'),
            'wellStockUnits' => Product::where('stock', '>', 0)->sum('stock'),
            'lowStockCount' => Product::where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'outOfStockCount' => Product::where('stock', 0)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'variantLabel' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProduct($request);
        $image = $data['image'] ?? null;
        $featuredImage = $data['featured_image'] ?? null;
        $variants = array_map(
            fn (array $variant) => [
                'name' => trim($variant['name']),
                'stock' => (int) $variant['stock'],
                'image' => $variant['image'] ?? null,
            ],
            $data['variants'] ?? []
        );
        unset($data['image'], $data['featured_image'], $data['variants']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_hot'] = $request->boolean('is_hot');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $variantLabel = $this->optionLabel($data['category']);
        $variants = $variantLabel ? $variants : [];

        if ($image) {
            $data['image_path'] = $image->store('products', 'public');
        }
        if ($featuredImage) {
            $data['featured_image_path'] = $featuredImage->store('products/featured', 'public');
        }
        if ($variantLabel && $variants) {
            $data['stock'] = array_sum(array_column($variants, 'stock'));
            $variants = array_map(function (array $variant): array {
                $image = $variant['image'];
                unset($variant['image']);
                $variant['image_path'] = $image ? $image->store('products/variants', 'public') : null;

                return $variant;
            }, $variants);
        } else {
            $variants = [];
        }

        DB::transaction(function () use ($data, $variants, $variantLabel) {
            $product = Product::create($data);
            if ($variantLabel) {
                $product->variants()->createMany($variants);
            }
        });

        return redirect()->route('admin.products.index')->with('status', 'Product added successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('admin.products.form', [
            'product' => $product,
            'variantLabel' => $this->optionLabel($product->category),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedProduct($request);
        $image = $data['image'] ?? null;
        $featuredImage = $data['featured_image'] ?? null;
        $variants = array_map(
            fn (array $variant) => [
                'name' => trim($variant['name']),
                'stock' => (int) $variant['stock'],
                'image' => $variant['image'] ?? null,
            ],
            $data['variants'] ?? []
        );
        unset($data['image'], $data['featured_image'], $data['variants']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_hot'] = $request->boolean('is_hot');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $variantLabel = $this->optionLabel($data['category']);
        $variants = $variantLabel ? $variants : [];
        $variantNames = array_column($variants, 'name');
        $existingVariants = $product->variants->keyBy('name');
        $variantsByName = collect($variants)->keyBy('name');
        $usedVariantNames = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereNotNull('color')
            ->pluck('color')
            ->unique()
            ->all();
        $removedUsedVariant = $product->variants()
            ->whereIn('name', $usedVariantNames)
            ->get()
            ->contains(fn ($variant) => ! in_array($variant->name, $variantNames, true));

        if ($removedUsedVariant) {
            throw ValidationException::withMessages([
                'variants' => ['A flavor or color used by an existing order cannot be removed. Set its stock to 0 instead.'],
            ]);
        }
        $oldImage = null;
        $oldFeaturedImage = null;
        $oldVariantImages = $existingVariants
            ->filter(fn (ProductVariant $variant) => ! $variantsByName->has($variant->name)
                || ! empty($variantsByName->get($variant->name)['image']))
            ->pluck('image_path')
            ->filter()
            ->all();

        if ($image) {
            $oldImage = $product->image_path;
            $data['image_path'] = $image->store('products', 'public');
        }
        if ($featuredImage) {
            $oldFeaturedImage = $product->featured_image_path;
            $data['featured_image_path'] = $featuredImage->store('products/featured', 'public');
        }
        if ($variantLabel && $variants) {
            $data['stock'] = array_sum(array_column($variants, 'stock'));
            $variants = array_map(function (array $variant) use ($existingVariants): array {
                $image = $variant['image'];
                $currentVariant = $existingVariants->get($variant['name']);
                unset($variant['image']);
                $variant['image_path'] = $image
                    ? $image->store('products/variants', 'public')
                    : $currentVariant?->image_path;

                return $variant;
            }, $variants);
        } else {
            $variants = [];
        }

        DB::transaction(function () use ($product, $data, $variants, $variantLabel) {
            $product->update($data);
            $variantNames = array_column($variants, 'name');
            if ($variantNames) {
                $product->variants()->whereNotIn('name', $variantNames)->delete();
            } else {
                $product->variants()->delete();
            }
            if ($variantLabel) {
                foreach ($variants as $variant) {
                    $product->variants()->updateOrCreate(
                        ['name' => $variant['name']],
                        [
                            'stock' => $variant['stock'],
                            'image_path' => $variant['image_path'],
                        ]
                    );
                }
            }
        });

        foreach (array_merge([$oldImage, $oldFeaturedImage], $oldVariantImages) as $oldPath) {
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->route('admin.products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $variantImages = $product->variants()->whereNotNull('image_path')->pluck('image_path')->all();
        foreach (array_merge([$product->image_path, $product->featured_image_path], $variantImages) as $imagePath) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted successfully.');
    }

    private function validatedProduct(Request $request): array
    {
        $variantLabel = $this->optionLabel((string) $request->input('category', ''));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_hot' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'variants' => ['nullable', 'array', 'min:1', 'max:30'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:50'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0', 'max:4294967295'],
            'variants.*.image' => ['nullable', 'image', 'max:2048'],
        ]);

        if (! empty($data['variants'])) {
            $names = array_map(fn (array $variant) => mb_strtolower(trim($variant['name'])), $data['variants']);
            if (count($names) !== count(array_unique($names))) {
                throw ValidationException::withMessages([
                    'variants' => ['Each flavor or color must have a unique name.'],
                ]);
            }
            if (array_sum(array_column($data['variants'], 'stock')) > 4294967295) {
                throw ValidationException::withMessages([
                    'variants' => ['The total variant stock cannot exceed 4,294,967,295.'],
                ]);
            }
        }

        return $data;
    }

    private function optionLabel(string $category): ?string
    {
        return (new Product(['category' => $category]))->optionLabel();
    }
}
