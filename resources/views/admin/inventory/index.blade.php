@extends('admin.layout')

@section('title', 'Inventory')

@section('content')
    <div class="page-heading inventory-heading">
        <div>
            <h1>Inventory</h1>
            <p>Monitor product quantities and stock status.</p>
        </div>
    </div>

    <nav class="inventory-tabs" aria-label="Inventory sections">
        <a class="inventory-tab active" href="{{ route('admin.inventory.index') }}" aria-current="page">Stock overview</a>
        <a class="inventory-tab" href="{{ route('admin.products.index') }}">Stock management</a>
    </nav>

    <section class="inventory-stats" aria-label="Inventory summary">
        <article class="inventory-stat blue">
            <span class="inventory-stat-label">TOTAL PRODUCTS</span>
            <strong>{{ number_format($productCount) }}</strong>
        </article>
        <article class="inventory-stat green">
            <span class="inventory-stat-label">IN STOCK</span>
            <strong>{{ number_format($inStockProductCount) }}</strong>
        </article>
        <article class="inventory-stat yellow">
            <span class="inventory-stat-label">LOW STOCKS</span>
            <strong>{{ number_format($lowStockCount) }}</strong>
        </article>
        <article class="inventory-stat red">
            <span class="inventory-stat-label">OUT OF STOCK</span>
            <strong>{{ number_format($outOfStockCount) }}</strong>
        </article>
    </section>

    <section class="panel inventory-panel" aria-label="Inventory products">
        <form class="inventory-filters" method="GET" action="{{ route('admin.inventory.index') }}">
            <label class="sr-only" for="inventory-search">Search products</label>
            <span class="search-field">
                <input id="inventory-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search..." aria-label="Search products">
                <span aria-hidden="true">⌕</span>
            </span>
            <label class="sr-only" for="inventory-category">Filter by category</label>
            <select id="inventory-category" name="category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="inventory-stock">Filter by stock status</label>
            <select id="inventory-stock" name="stock">
                <option value="">All stock statuses</option>
                <option value="in_stock" @selected(request('stock') === 'in_stock')>In stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="empty" @selected(request('stock') === 'empty')>Out of stock</option>
            </select>
            <label class="sr-only" for="inventory-sort">Sort inventory</label>
            <select id="inventory-sort" name="sort">
                <option value="">Sort by: Newest</option>
                <option value="low" @selected(request('sort') === 'low')>Sort by: Low stocks</option>
                <option value="name" @selected(request('sort') === 'name')>Sort by: Name</option>
                <option value="stock" @selected(request('sort') === 'stock')>Sort by: Most stock</option>
            </select>
            <button class="filter-submit" type="submit">Apply</button>
            @if (request()->hasAny(['q', 'stock', 'category', 'sort']))
                <a class="inventory-clear-link" href="{{ route('admin.inventory.index') }}">Clear</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                {{ request()->hasAny(['q', 'stock', 'category']) ? 'No inventory items match these filters.' : 'No products in inventory yet.' }}
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table inventory-table">
                    <thead>
                        <tr><th>PRODUCT NAME</th><th>CATEGORY</th><th>CURRENT STOCK</th><th>STATUS</th><th class="actions-heading">ACTIONS</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <span class="product-thumb">
                                        @if ($product->image_path)
                                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="">
                                        @else
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($product->name, 0, 1)) }}
                                        @endif
                                    </span>
                                    <span>
                                        <span class="product-name">{{ $product->name }}</span>
                                        <span class="product-meta">{{ $product->brand ?: 'No brand' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $product->category }}</td>
                            <td class="current-stock">{{ number_format($product->stock) }}</td>
                            <td>
                                <span class="stock-status {{ $product->stock === 0 ? 'status-empty' : ($product->stock <= 10 ? 'status-low' : 'status-good') }}">
                                    {{ $product->stock === 0 ? 'Out of stock' : ($product->stock <= 10 ? 'Low stock' : 'In stock') }}
                                </span>
                            </td>
                            <td>
                                <div class="inventory-actions">
                                    <a class="icon-action edit-action" href="{{ route('admin.products.edit', $product) }}" aria-label="Edit {{ $product->name }}" title="Edit product">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                    </a>
                                    <form id="delete-inventory-product-{{ $product->id }}" method="POST" action="{{ route('admin.products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action delete-action" type="button" data-inventory-delete-form="delete-inventory-product-{{ $product->id }}" data-product-name="{{ $product->name }}" aria-label="Delete {{ $product->name }}" title="Delete product">
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
            <div class="pagination pagination-window inventory-footer">
                <span>Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }} products · Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                @if ($products->hasPages())
                    {{ $products->links('admin.pagination.scroll') }}
                @endif
            </div>
        @endif
    </section>

    <dialog class="delete-confirm-dialog inventory-delete-dialog" id="inventoryDeleteDialog" aria-labelledby="inventoryDeleteTitle" aria-describedby="inventoryDeleteMessage">
        <div class="delete-dialog-icon" aria-hidden="true">!</div>
        <h2 id="inventoryDeleteTitle">Delete product?</h2>
        <p id="inventoryDeleteMessage">This action cannot be undone.</p>
        <div class="delete-dialog-actions">
            <form method="dialog">
                <button class="secondary-button" type="submit">Cancel</button>
            </form>
            <button class="delete-confirm-button" id="confirmInventoryDelete" type="button">Delete product</button>
        </div>
    </dialog>
@endsection

@push('styles')
    <style>
        .inventory-heading { margin-bottom: 12px; }
        .inventory-heading h1 { font-size: 19px; }
        .inventory-tabs { display: flex; gap: 8px; margin-bottom: 12px; }
        .inventory-tab { display: inline-flex; min-height: 27px; align-items: center; padding: 0 13px; border: 1px solid #302a3b; border-radius: 5px; background: #17141f; color: #aaa4b2; font-size: 9px; text-decoration: none; }
        .inventory-tab:hover { border-color: #5434a4; color: #eee8ff; }
        .inventory-tab.active { border-color: #572ce2; background: linear-gradient(100deg, #5127d2, #7140e8); color: #fff; }
        .inventory-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 11px; }
        .inventory-stat { display: flex; min-height: 63px; flex-direction: column; justify-content: center; padding: 8px 12px; border: 1px solid; border-radius: 5px; background: rgba(15, 14, 22, .9); text-align: center; }
        .inventory-stat-label { font-size: 8px; }
        .inventory-stat strong { margin-top: 3px; font-family: 'Outfit', sans-serif; font-size: 23px; font-weight: 500; line-height: 1.1; }
        .inventory-stat.blue { border-color: rgba(45, 122, 245, .44); color: #318bff; background: rgba(20, 49, 103, .14); }
        .inventory-stat.green { border-color: rgba(49, 201, 25, .48); color: #31df19; background: rgba(26, 84, 19, .13); }
        .inventory-stat.yellow { border-color: rgba(215, 187, 0, .45); color: #f0d900; background: rgba(98, 83, 0, .12); }
        .inventory-stat.red { border-color: rgba(228, 24, 54, .43); color: #ff314c; background: rgba(99, 14, 30, .13); }
        .inventory-panel { overflow: hidden; }
        .inventory-filters { display: flex; align-items: center; gap: 8px; padding: 9px 10px; border-bottom: 1px solid #26212f; }
        .search-field { position: relative; flex: 1; min-width: 115px; }
        .search-field input, .inventory-filters select { width: 100%; height: 27px; padding: 0 9px; border: 1px solid #302a3b; border-radius: 5px; outline: 0; background: #13111a; color: #ded9e7; font: inherit; font-size: 9px; }
        .search-field input { padding-right: 28px; }
        .search-field input:focus, .inventory-filters select:focus { border-color: #7045eb; }
        .search-field > span { position: absolute; top: 3px; right: 9px; color: #8d8798; font-size: 15px; pointer-events: none; }
        .inventory-filters select { width: auto; min-width: 105px; }
        .inventory-filters select:last-of-type { min-width: 120px; }
        .filter-submit { min-height: 27px; padding: 0 11px; border: 1px solid #5235a0; border-radius: 5px; background: #211a34; color: #d7caff; font: inherit; font-size: 9px; cursor: pointer; }
        .filter-submit:hover { background: #32234f; }
        .inventory-clear-link { color: #a995dc; font-size: 9px; text-decoration: none; white-space: nowrap; }
        .inventory-clear-link:hover { color: #fff; }
        .inventory-table { min-width: 580px; }
        .inventory-table th { padding: 8px 10px; font-size: 8px; }
        .inventory-table td { padding: 7px 10px; font-size: 9px; }
        .inventory-table .product-cell { gap: 7px; }
        .inventory-table .product-thumb { width: 31px; height: 31px; flex-basis: 31px; }
        .inventory-table .product-name { font-size: 9px; }
        .inventory-table .product-meta { font-size: 8px; }
        .current-stock { width: 110px; color: #e7e2ee !important; }
        .stock-status { display: inline-flex; min-height: 17px; align-items: center; padding: 0 7px; border: 1px solid; border-radius: 4px; font-size: 8px; white-space: nowrap; }
        .status-good { border-color: rgba(47, 186, 126, .28); background: rgba(28, 125, 81, .1); color: #60d9a1; }
        .status-low { border-color: rgba(225, 77, 97, .35); background: rgba(151, 34, 52, .12); color: #ff7888; }
        .status-empty { border-color: rgba(225, 77, 97, .35); background: rgba(151, 34, 52, .12); color: #ff7888; }
        .actions-heading { text-align: center !important; }
        .inventory-actions { display: flex; justify-content: center; align-items: center; gap: 6px; }
        .inventory-actions form { display: flex; margin: 0; }
        .icon-action { display: grid; width: 21px; height: 23px; place-items: center; padding: 3px; border: 0; border-radius: 4px; background: transparent; cursor: pointer; }
        .icon-action:hover { background: #211b2c; }
        .icon-action svg { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .edit-action { color: #aaa4b2; }
        .delete-action { color: #fb4258; }
        .inventory-footer { display: flex; min-height: 34px; align-items: center; justify-content: space-between; gap: 10px; padding: 4px 11px; border-top: 1px solid #26212f; color: #797382; font-size: 8px; }
        .delete-confirm-dialog { width: min(390px, calc(100vw - 32px)); padding: 24px; border: 1px solid #393044; border-radius: 12px; background: #17141f; color: #f4f1ff; box-shadow: 0 22px 70px rgba(0,0,0,.55); }
        .delete-confirm-dialog::backdrop { background: rgba(5,4,8,.72); backdrop-filter: blur(3px); }
        .delete-dialog-icon { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(255,120,136,.3); border-radius: 50%; background: rgba(151,34,52,.15); color: #ff7888; font-size: 20px; font-weight: 700; }
        .delete-confirm-dialog h2 { margin: 15px 0 6px; font: 600 17px 'Outfit', sans-serif; }
        .delete-confirm-dialog p { margin: 0; color: #aaa4b2; font-size: 11px; line-height: 1.5; }
        .delete-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
        .delete-dialog-actions form { margin: 0; }
        .delete-confirm-button { min-height: 32px; padding: 0 12px; border: 1px solid #9a3445; border-radius: 6px; background: #8d293a; color: #fff; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .delete-confirm-button:hover { background: #a83246; }
        @media (max-width: 760px) { .inventory-filters { flex-wrap: wrap; }.search-field { flex-basis: 100%; }.inventory-filters select { flex: 1; }.inventory-stats { gap: 7px; } }
        @media (max-width: 420px) { .inventory-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }.inventory-footer { align-items: flex-start; flex-direction: column; padding: 8px 10px; } }
    </style>
@endpush

@push('scripts')
    <script>
        const inventoryDeleteDialog = document.getElementById('inventoryDeleteDialog');
        const inventoryDeleteMessage = document.getElementById('inventoryDeleteMessage');
        const confirmInventoryDelete = document.getElementById('confirmInventoryDelete');
        let pendingInventoryDeleteForm = null;

        document.querySelectorAll('[data-inventory-delete-form]').forEach(button => {
            button.addEventListener('click', () => {
                pendingInventoryDeleteForm = document.getElementById(button.dataset.inventoryDeleteForm);
                inventoryDeleteMessage.textContent = `Delete "${button.dataset.productName}"? This action cannot be undone.`;
                if (typeof inventoryDeleteDialog.showModal !== 'function') {
                    if (window.confirm(inventoryDeleteMessage.textContent)) pendingInventoryDeleteForm.requestSubmit();
                    return;
                }
                inventoryDeleteDialog.showModal();
                confirmInventoryDelete.focus();
            });
        });

        confirmInventoryDelete.addEventListener('click', () => pendingInventoryDeleteForm?.requestSubmit());
        inventoryDeleteDialog.addEventListener('click', event => {
            if (event.target === inventoryDeleteDialog) inventoryDeleteDialog.close();
        });
    </script>
@endpush
