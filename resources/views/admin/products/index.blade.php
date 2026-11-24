@extends('admin.layout')

@section('title', 'Products')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Products</h1>
            <p>Manage your store catalog and product details.</p>
        </div>
        <a class="primary-button" href="{{ route('admin.products.create') }}">+ Add product</a>
    </div>

    <section class="panel product-catalog">
        <form class="product-filters" method="GET" action="{{ route('admin.products.index') }}">
            <label class="sr-only" for="product-search">Search products</label>
            <span class="product-search">
                <input id="product-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search..." aria-label="Search products">
                <span aria-hidden="true">⌕</span>
            </span>
            <label class="sr-only" for="product-category">Filter by category</label>
            <select id="product-category" name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <button class="filter-submit" type="submit">Apply</button>
            @if (request()->hasAny(['q', 'category']))
                <a class="clear-link" href="{{ route('admin.products.index') }}">Clear</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                {{ request()->hasAny(['q', 'category']) ? 'No products match these filters.' : 'No products yet. Add a product to get started.' }}
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table products-table">
                    <thead>
                        <tr><th>IMAGE</th><th>PRODUCT NAME</th><th>CATEGORY</th><th>STOCK</th><th>PRICE</th><th>STATUS</th><th>ACTION</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                <span class="catalog-thumb">
                                    @if ($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="">
                                    @else
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($product->name, 0, 1)) }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="product-name">{{ $product->name }}</span>
                                <span class="product-meta">{{ $product->brand ?: 'No brand' }}</span>
                            </td>
                            <td><span class="category-label">{{ $product->category }}</span></td>
                            <td><strong class="catalog-stock {{ $product->stock === 0 ? 'empty' : ($product->stock <= 10 ? 'low' : '') }}">{{ number_format($product->stock) }}</strong></td>
                            <td>₱{{ number_format((float) $product->price, 2) }}</td>
                            <td>
                                <div class="product-statuses">
                                    <span class="badge {{ $product->stock === 0 ? 'danger' : ($product->stock <= 10 ? 'warning' : 'good') }}">
                                        {{ $product->stock === 0 ? 'Out of stock' : ($product->stock <= 10 ? 'Low stock' : 'In stock') }}
                                    </span>
                                    @unless ($product->is_active)
                                        <span class="badge">Hidden</span>
                                    @endunless
                                    @if ($product->is_featured)
                                        <span class="badge featured-badge">Featured</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="product-actions">
                                    <a class="icon-action edit-action" href="{{ route('admin.products.edit', $product) }}" aria-label="Edit {{ $product->name }}" title="Edit product">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </a>
                                    <form id="delete-product-{{ $product->id }}" method="POST" action="{{ route('admin.products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action delete-action" type="button" data-delete-form="delete-product-{{ $product->id }}" data-product-name="{{ $product->name }}" aria-label="Delete {{ $product->name }}" title="Delete product">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination pagination-window">
                <span>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} products · Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                @if ($products->hasPages())
                    {{ $products->links('admin.pagination.scroll') }}
                @endif
            </div>
        @endif
    </section>

    <dialog class="delete-confirm-dialog" id="deleteConfirmDialog" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
        <div class="delete-dialog-icon" aria-hidden="true">!</div>
        <h2 id="deleteConfirmTitle">Delete product?</h2>
        <p id="deleteConfirmMessage">This action cannot be undone.</p>
        <div class="delete-dialog-actions">
            <form method="dialog">
                <button class="secondary-button" type="submit">Cancel</button>
            </form>
            <button class="delete-confirm-button" id="confirmProductDelete" type="button">Delete product</button>
        </div>
    </dialog>
@endsection

@push('styles')
    <style>
        .product-catalog { overflow: hidden; }
        .product-filters { display: flex; align-items: center; gap: 9px; margin: 14px 14px 0; padding-bottom: 11px; border-bottom: 1px solid #26212f; }
        .product-search { position: relative; flex: 1; }
        .product-search input, .product-filters select { width: 100%; height: 31px; padding: 0 10px; border: 1px solid #302a3b; border-radius: 5px; outline: 0; background: #13111a; color: #eee; font: inherit; font-size: 10px; }
        .product-search input { padding-right: 28px; }
        .product-search input:focus, .product-filters select:focus { border-color: #7045eb; }
        .product-search > span { position: absolute; top: 4px; right: 9px; color: #8d8798; font-size: 16px; pointer-events: none; }
        .product-filters select { width: min(150px, 27vw); }
        .filter-submit { min-height: 31px; padding: 0 12px; border: 1px solid #5235a0; border-radius: 5px; background: #211a34; color: #d7caff; font: inherit; font-size: 9px; cursor: pointer; }
        .filter-submit:hover { background: #32234f; }
        .clear-link { color: #a995dc; font-size: 9px; text-decoration: none; }
        .clear-link:hover { color: #fff; }
        .products-table { min-width: 680px; }
        .products-table th, .products-table td { padding: 9px 10px; }
        .catalog-thumb { display: grid; width: 46px; height: 46px; place-items: center; overflow: hidden; border: 1px solid #322c3e; border-radius: 5px; background: #15121c; color: #8d829f; font-size: 12px; }
        .catalog-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .products-table .product-name { display: block; max-width: 190px; overflow: hidden; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
        .products-table .product-meta { font-size: 8px; }
        .category-label { display: inline-flex; min-height: 19px; align-items: center; padding: 0 7px; border: 1px solid #302a3b; border-radius: 4px; background: #17141f; color: #c5b9e8; font-size: 9px; white-space: nowrap; }
        .catalog-stock { color: #60d9a1; font-weight: 600; }
        .catalog-stock.low { color: #e8c35f; }
        .catalog-stock.empty { color: #ff7888; }
        .product-statuses { display: flex; flex-wrap: wrap; gap: 4px; }
        .product-statuses .badge { min-height: 18px; font-size: 8px; }
        .product-statuses .featured-badge { border-color: rgba(165,130,255,.3); background: rgba(112,69,235,.12); color: #c4b1ff; }
        .product-actions { display: flex; align-items: center; gap: 4px; }
        .product-actions form { display: flex; margin: 0; }
        .icon-action { display: grid; width: 23px; height: 24px; place-items: center; padding: 4px; border: 0; border-radius: 4px; background: transparent; cursor: pointer; }
        .icon-action:hover { background: #211b2c; }
        .icon-action svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .edit-action { color: #aaa4b2; }
        .delete-action { color: #fb4258; }
        .product-catalog > .pagination { border-top: 1px solid #26212f; }
        .delete-confirm-dialog { width: min(390px, calc(100vw - 32px)); padding: 24px; border: 1px solid #393044; border-radius: 12px; background: #17141f; color: #f4f1ff; box-shadow: 0 22px 70px rgba(0,0,0,.55); }
        .delete-confirm-dialog::backdrop { background: rgba(5,4,8,.72); backdrop-filter: blur(3px); }
        .delete-dialog-icon { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(255,120,136,.3); border-radius: 50%; background: rgba(151,34,52,.15); color: #ff7888; font-size: 20px; font-weight: 700; }
        .delete-confirm-dialog h2 { margin: 15px 0 6px; font: 600 17px 'Outfit', sans-serif; }
        .delete-confirm-dialog p { margin: 0; color: #aaa4b2; font-size: 11px; line-height: 1.5; }
        .delete-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
        .delete-dialog-actions form { margin: 0; }
        .delete-confirm-button { min-height: 32px; padding: 0 12px; border: 1px solid #9a3445; border-radius: 6px; background: #8d293a; color: #fff; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .delete-confirm-button:hover { background: #a83246; }
        @media (max-width: 620px) { .product-filters { flex-wrap: wrap; }.product-search { flex-basis: 100%; }.product-filters select { flex: 1; width: auto; } }
    </style>
@endpush

@push('scripts')
    <script>
        const deleteConfirmDialog = document.getElementById('deleteConfirmDialog');
        const deleteConfirmMessage = document.getElementById('deleteConfirmMessage');
        const confirmProductDelete = document.getElementById('confirmProductDelete');
        let pendingDeleteForm = null;

        document.querySelectorAll('[data-delete-form]').forEach(button => {
            button.addEventListener('click', () => {
                pendingDeleteForm = document.getElementById(button.dataset.deleteForm);
                deleteConfirmMessage.textContent = `Delete "${button.dataset.productName}"? This action cannot be undone.`;
                deleteConfirmDialog.showModal();
                confirmProductDelete.focus();
            });
        });

        confirmProductDelete.addEventListener('click', () => {
            pendingDeleteForm?.requestSubmit();
        });

        deleteConfirmDialog.addEventListener('click', event => {
            if (event.target === deleteConfirmDialog) deleteConfirmDialog.close();
        });
    </script>
@endpush
