@extends('admin.layout')

@section('title', $product->exists ? 'Edit product' : 'Add product')

@section('content')
    <div class="page-heading">
        <div>
            <h1>{{ $product->exists ? 'Edit product' : 'Add product' }}</h1>
            <p>{{ $product->exists ? 'Update product details and inventory.' : 'Add a new item to your store catalog.' }}</p>
        </div>
        <a class="secondary-button" href="{{ route('admin.products.index') }}">Back to products</a>
    </div>

    @if ($errors->any())
        <div class="form-alert" role="alert">Please check the highlighted fields and try again.</div>
    @endif

    <form
        class="product-form"
        method="POST"
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
        enctype="multipart/form-data"
    >
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="form-column">
            <section class="panel form-panel">
                <div class="form-panel-heading">
                    <h2 class="panel-title">Product image</h2>
                    <p class="panel-subtitle">Upload a clear product photo (JPG, PNG, or WebP; up to 2 MB).</p>
                </div>
                <label class="image-drop" for="image">
                    @if ($product->image_path)
                        <img id="image-preview" src="{{ asset('storage/' . $product->image_path) }}" alt="Current product image">
                    @else
                        <img id="image-preview" alt="Product image preview" hidden>
                        <span class="image-placeholder" id="image-placeholder"><span class="upload-icon">+</span><strong>Choose a product image</strong><span>Click to browse your files</span></span>
                    @endif
                    <input type="file" id="image" name="image" accept="image/*">
                </label>
                @error('image')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </section>

            <section class="panel form-panel">
                <div class="form-panel-heading">
                    <h2 class="panel-title">Featured promotional image</h2>
                    <p class="panel-subtitle">Optional carousel artwork. The carousel frame adapts to show the full image without cropping. Wide 3:1 artwork (1200 × 400 px recommended) works best. This does not replace the catalog photo.</p>
                </div>
                <label class="image-drop" for="featured_image">
                    @if ($product->featured_image_path)
                        <img id="featured-image-preview" src="{{ asset('storage/' . $product->featured_image_path) }}" alt="Current featured promotional image">
                    @else
                        <img id="featured-image-preview" alt="Featured promotional image preview" hidden>
                        <span class="image-placeholder" id="featured-image-placeholder"><span class="upload-icon">+</span><strong>Choose promotional artwork</strong><span>Use wide 3:1 artwork for the best fit</span></span>
                    @endif
                    <input type="file" id="featured_image" name="featured_image" accept="image/*">
                </label>
                @error('featured_image')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </section>

            <section class="panel form-panel">
                <div class="form-panel-heading">
                    <h2 class="panel-title">Product details</h2>
                    <p class="panel-subtitle">Describe the item customers will find in your store.</p>
                </div>
                <div class="form-field">
                    <label for="name">Product name <span>*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-two">
                    <div class="form-field">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" maxlength="255" placeholder="e.g. Vaporesso">
                        @error('brand')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        @php
                            $categoryValue = old('category', $product->category);
                            $standardCategories = ['Juices', 'Pod System', 'Disposable Vapes'];
                            $isCustomCategory = $categoryValue && ! in_array($categoryValue, $standardCategories, true);
                        @endphp
                        <label class="category-field-label" for="category-select">Category <span>*</span></label>
                        <select id="category-select" required>
                            <option value="" disabled @selected(! $categoryValue)>Select a category</option>
                            @foreach ($standardCategories as $categoryOption)
                                <option value="{{ $categoryOption }}" @selected($categoryValue === $categoryOption)>{{ $categoryOption }}</option>
                            @endforeach
                            <option value="custom" @selected($isCustomCategory)>Custom category</option>
                        </select>
                        <input type="hidden" id="category" name="category" value="{{ $categoryValue }}">
                        <div class="form-field custom-category-field" id="custom-category-field" @if (! $isCustomCategory) hidden @endif>
                            <label for="custom-category">Enter custom category <span>*</span></label>
                            <input type="text" id="custom-category" value="{{ $isCustomCategory ? $categoryValue : '' }}" maxlength="100" placeholder="Type a category">
                        </div>
                        @error('category')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" maxlength="5000" placeholder="Add product details...">{{ old('description', $product->description) }}</textarea>
                    @error('description')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <div class="form-column">
            <section class="panel form-panel">
                <div class="form-panel-heading">
                    <h2 class="panel-title">Pricing and inventory</h2>
                    <p class="panel-subtitle">Set the selling price and available stock.</p>
                </div>
                <div class="form-field">
                    <label for="price">Price (PHP) <span>*</span></label>
                    <div class="input-prefix"><span>₱</span><input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" min="0" max="99999999.99" step="0.01" required></div>
                    @error('price')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                @php
                    $variantRows = old('variants', $product->exists
                        ? $product->variants->map(fn ($variant) => ['name' => $variant->name, 'stock' => $variant->stock, 'image_path' => $variant->image_path])->all()
                        : []);
                @endphp
                <section class="variant-editor" id="variant-editor" data-label="{{ $variantLabel ?? '' }}" hidden>
                    <div class="form-panel-heading">
                        <h3 class="panel-title">Available <span id="variant-label">{{ $variantLabel }}</span>s</h3>
                        <p class="panel-subtitle">Set the available quantity for each option.</p>
                    </div>
                    <div class="variant-list-scroll">
                        <div class="variant-list" id="variant-list">
                            @foreach ($variantRows as $index => $variant)
                                <div class="variant-row">
                                    <label>
                                        <span class="variant-row-label">{{ $variantLabel }}</span>
                                        <input type="text" name="variants[{{ $index }}][name]" value="{{ $variant['name'] }}" maxlength="100" placeholder="e.g. Blueberry Ice" required>
                                    </label>
                                    <label>
                                        <span>Stock</span>
                                        <input type="number" name="variants[{{ $index }}][stock]" value="{{ $variant['stock'] }}" min="0" step="1" required>
                                    </label>
                                    <label class="variant-image-picker">
                                        <span class="variant-image-label">Option image</span>
                                        <span class="variant-image-preview">
                                            @if (! empty($variant['image_path']))
                                                <img src="{{ asset('storage/' . $variant['image_path']) }}" alt="Current option image">
                                            @else
                                                <span aria-hidden="true">+</span>
                                            @endif
                                        </span>
                                        <input class="variant-image-input" type="file" name="variants[{{ $index }}][image]" accept="image/*">
                                    </label>
                                    <button class="remove-variant" type="button" aria-label="Remove option">×</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button class="secondary-button add-variant" id="add-variant" type="button">+ Add option</button>
                </section>
                <div class="form-field">
                    <label for="stock"><span id="stock-label">Stock quantity</span> <span>*</span></label>
                    <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0" step="1" required>
                    <small class="stock-hint" id="stock-hint" hidden>Overall stock is calculated from the stock of all options.</small>
                    @error('stock')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <label class="active-toggle" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked($errors->any() ? old('is_active') : ($product->exists ? $product->is_active : true))>
                    <span><strong>Visible in store</strong><small>Active products can be shown to customers.</small></span>
                </label>
                <label class="active-toggle" for="is_featured">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" @checked($errors->any() ? old('is_featured') : ($product->exists && $product->is_featured))>
                    <span><strong>Include in homepage carousel</strong><small>Every active product with this enabled gets a carousel slide. Add as many as you want; there is no fixed slide limit.</small></span>
                </label>
                <label class="active-toggle" for="is_hot">
                    <input type="checkbox" id="is_hot" name="is_hot" value="1" @checked($errors->any() ? old('is_hot') : ($product->exists && $product->is_hot))>
                    <span><strong>Hot item</strong><small>Show a Hot label on this product in the store.</small></span>
                </label>
                <label class="active-toggle" for="is_best_seller">
                    <input type="checkbox" id="is_best_seller" name="is_best_seller" value="1" @checked($errors->any() ? old('is_best_seller') : ($product->exists && $product->is_best_seller))>
                    <span><strong>Best seller</strong><small>Show a Best Seller label on this product in the store.</small></span>
                </label>
            </section>

            <section class="panel form-panel form-actions">
                <button class="primary-button save-button" type="submit">{{ $product->exists ? 'Save changes' : 'Add product' }}</button>
                <a class="secondary-button cancel-button" href="{{ route('admin.products.index') }}">Cancel</a>
                @if ($product->exists)
                    <button class="delete-button" type="button" id="openDeleteConfirm">Delete product</button>
                @endif
            </section>
        </div>
    </form>

    @if ($product->exists)
        <form id="delete-product" method="POST" action="{{ route('admin.products.destroy', $product) }}">
            @csrf
            @method('DELETE')
        </form>
        <dialog class="delete-confirm-dialog" id="deleteConfirmDialog" aria-labelledby="deleteConfirmTitle" aria-describedby="deleteConfirmMessage">
            <div class="delete-dialog-icon" aria-hidden="true">!</div>
            <h2 id="deleteConfirmTitle">Delete product?</h2>
            <p id="deleteConfirmMessage">Delete “{{ $product->name }}”? This action cannot be undone.</p>
            <div class="delete-dialog-actions">
                <form method="dialog">
                    <button class="secondary-button" type="submit">Cancel</button>
                </form>
                <button class="delete-confirm-button" id="confirmProductDelete" type="button">Delete product</button>
            </div>
        </dialog>
    @endif
@endsection

@push('styles')
    <style>
        .form-alert { margin-bottom: 14px; padding: 10px 12px; border: 1px solid rgba(255, 102, 127, .3); border-radius: 6px; background: rgba(130, 30, 50, .15); color: #ff9aa7; font-size: 10px; }
        .product-form { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(250px, .85fr); align-items: start; gap: 13px; }
        .form-column { display: grid; gap: 13px; }
        .form-panel { padding: 15px; }
        .form-panel-heading { margin-bottom: 16px; }
        .form-field { margin-top: 13px; }
        .form-field:first-of-type { margin-top: 0; }
        .form-field label { display: block; margin-bottom: 7px; color: #bcb6c7; font-size: 10px; font-weight: 600; }
        .form-field label span { color: #b397ff; }
        .form-field input, .form-field textarea { display: block; width: 100%; min-height: 35px; padding: 8px 10px; border: 1px solid #302a3b; border-radius: 5px; outline: 0; background: #121019; color: #f0edf5; font: inherit; font-size: 10px; transition: border-color .18s, box-shadow .18s; }
        .form-field select { display: block; width: 100%; min-height: 35px; padding: 0 10px; border: 1px solid #302a3b; border-radius: 5px; outline: 0; background: #121019; color: #f0edf5; font: inherit; font-size: 10px; line-height: normal; }
        #category[type="hidden"] { display: none; }
        .custom-category-field { margin-top: 10px; }
        .custom-category-field[hidden] { display: none; }
        .form-field textarea { min-height: 98px; resize: vertical; line-height: 1.55; }
        .form-field input:focus, .form-field textarea:focus { border-color: #7045eb; box-shadow: 0 0 0 2px rgba(112, 69, 235, .15); }
        .form-field input::placeholder, .form-field textarea::placeholder { color: #696373; }
        .stock-hint { display: block; margin-top: 6px; color: #8d8798; font-size: 9px; line-height: 1.4; }
        .form-two { display: grid; grid-template-columns: 1fr 1fr; gap: 11px; }
        .form-two > .form-field { margin-top: 0; }
        .form-two > .form-field { display: flex; flex-direction: column; }
        .form-two > .form-field > label { min-height: 12px; margin-bottom: 7px; line-height: 12px; }
        .form-two > .form-field > .category-field-label { top: 5px; position: relative; }
        .form-two > .form-field > input, .form-two > .form-field > select { box-sizing: border-box; height: 35px; min-height: 35px; }
        .input-prefix { position: relative; }
        .input-prefix > span { position: absolute; top: 9px; left: 10px; color: #958b9f; font-size: 11px; }
        .input-prefix input { padding-left: 26px; }
        .image-drop { display: grid; min-height: 155px; place-items: center; overflow: hidden; border: 1px dashed #45375f; border-radius: 6px; background: linear-gradient(145deg, rgba(42, 31, 65, .25), rgba(18, 16, 25, .6)); color: #958b9f; cursor: pointer; transition: border-color .18s, background .18s; }
        .image-drop:hover { border-color: #8a67e7; background: rgba(51, 36, 82, .25); }
        .image-drop > img:not([hidden]) { display: block; width: 100%; height: 210px; object-fit: contain; background: #100e16; }
        .image-drop input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; clip-path: inset(50%); }
        .image-drop:focus-within { outline: 2px solid #8357ff; outline-offset: 2px; }
        .image-placeholder { display: grid; justify-items: center; gap: 7px; padding: 20px; text-align: center; }
        .image-placeholder strong { color: #c5becf; font-size: 10px; }
        .image-placeholder > span:last-child { color: #7d7688; font-size: 9px; }
        .upload-icon { display: grid; width: 28px; height: 28px; place-items: center; border: 1px solid #493a68; border-radius: 50%; color: #b49bff; font-size: 18px; }
        .variant-editor { margin-top: 20px; padding-top: 16px; border-top: 1px solid #302a3b; }
        .variant-editor[hidden] { display: none; }
        .variant-editor .form-panel-heading { margin-bottom: 10px; }
        .variant-list-scroll { max-height: 270px; overflow-y: auto; overscroll-behavior: contain; padding-right: 5px; }
        .variant-list { display: grid; gap: 8px; }
        .variant-row { display: grid; grid-template-columns: minmax(0, 1fr) 68px minmax(105px, 132px) 28px; gap: 7px; align-items: end; }
        .variant-row label > span { display: block; margin-bottom: 5px; color: #a9a2b4; font-size: 9px; }
        .variant-row input { min-width: 0; width: 100%; min-height: 32px; padding: 6px 8px; border: 1px solid #302a3b; border-radius: 5px; background: #121019; color: #f0edf5; font: inherit; font-size: 10px; }
        .variant-row .variant-image-picker { position: relative; display: flex; min-width: 0; min-height: 50px; align-items: center; gap: 7px; margin: 0; padding: 5px; border: 1px solid #302a3b; border-radius: 5px; background: #121019; cursor: pointer; }
        .variant-row .variant-image-label { min-width: 0; margin: 0; overflow: hidden; color: #a9a2b4; font-size: 8px; text-overflow: ellipsis; white-space: nowrap; }
        .variant-image-preview { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; overflow: hidden; border: 1px dashed #493a68; border-radius: 4px; color: #b49bff; font-size: 17px; }
        .variant-image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .variant-row .variant-image-input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); clip-path: inset(50%); }
        .variant-image-picker:focus-within { outline: 2px solid #8357ff; outline-offset: 2px; }
        .remove-variant { width: 28px; height: 32px; border: 1px solid #49303c; border-radius: 5px; color: #e88b9b; font-size: 17px; }
        .add-variant { margin-top: 9px; padding: 7px 10px; font-size: 9px; }
        .active-toggle { display: flex; align-items: flex-start; gap: 9px; margin-top: 19px; padding: 11px; border: 1px solid #2c2735; border-radius: 5px; cursor: pointer; }
        .active-toggle input { margin: 2px 0 0; accent-color: #7045eb; }
        .active-toggle strong, .active-toggle small { display: block; }
        .active-toggle strong { color: #ddd7e7; font-size: 10px; }
        .active-toggle small { margin-top: 4px; color: #7f7889; font-size: 9px; line-height: 1.4; }
        .form-actions { display: grid; gap: 8px; }
        .form-actions .primary-button, .form-actions .secondary-button { width: 100%; min-height: 35px; }
        .delete-button { min-height: 32px; border: 0; background: transparent; color: #e77b88; font: inherit; font-size: 9px; cursor: pointer; }
        .delete-button:hover { color: #ff9aa7; }
        .delete-confirm-dialog { width: min(390px, calc(100vw - 32px)); padding: 24px; border: 1px solid #393044; border-radius: 12px; background: #17141f; color: #f4f1ff; box-shadow: 0 22px 70px rgba(0,0,0,.55); }
        .delete-confirm-dialog::backdrop { background: rgba(5,4,8,.72); backdrop-filter: blur(3px); }
        .delete-dialog-icon { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(255,120,136,.3); border-radius: 50%; background: rgba(151,34,52,.15); color: #ff7888; font-size: 20px; font-weight: 700; }
        .delete-confirm-dialog h2 { margin: 15px 0 6px; font: 600 17px 'Outfit', sans-serif; }
        .delete-confirm-dialog p { margin: 0; color: #aaa4b2; font-size: 11px; line-height: 1.5; }
        .delete-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
        .delete-dialog-actions form { margin: 0; }
        .delete-confirm-button { min-height: 32px; padding: 0 12px; border: 1px solid #9a3445; border-radius: 6px; background: #8d293a; color: #fff; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .delete-confirm-button:hover { background: #a83246; }
        @media (max-width: 780px) { .product-form { grid-template-columns: 1fr; } }
        @media (max-width: 460px) {
            .form-two { grid-template-columns: 1fr; gap: 0; }
            .form-field input, .form-field textarea, .form-field select { min-height: 44px; font-size: 14px; }
            .form-field textarea { min-height: 110px; }
            .variant-row { grid-template-columns: minmax(0, 1fr) 78px 30px; gap: 8px; }
            .variant-row .variant-image-picker { grid-column: 1 / -1; min-height: 58px; }
            .variant-image-preview { width: 44px; height: 44px; flex-basis: 44px; }
            .variant-row input { min-height: 40px; font-size: 14px; }
            .variant-row .variant-image-input { min-height: 0; }
            .remove-variant { width: 30px; height: 40px; }
            .add-variant { min-height: 42px; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const categorySelect = document.getElementById('category-select');
        const categoryInput = document.getElementById('category');
        const customCategoryField = document.getElementById('custom-category-field');
        const customCategoryInput = document.getElementById('custom-category');
        const stockInput = document.getElementById('stock');
        const variantEditor = document.getElementById('variant-editor');
        const variantList = document.getElementById('variant-list');
        const addVariantButton = document.getElementById('add-variant');
        const openDeleteConfirm = document.getElementById('openDeleteConfirm');
        const deleteConfirmDialog = document.getElementById('deleteConfirmDialog');
        const confirmProductDelete = document.getElementById('confirmProductDelete');

        [['image', 'image-preview', 'image-placeholder'], ['featured_image', 'featured-image-preview', 'featured-image-placeholder']]
            .forEach(([inputId, previewId, placeholderId]) => {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);
                const placeholder = document.getElementById(placeholderId);

                input?.addEventListener('change', () => {
                    const [file] = input.files;
                    if (!file) return;

                    preview.src = URL.createObjectURL(file);
                    preview.hidden = false;
                    if (placeholder) placeholder.hidden = true;
                });
            });

        function optionLabel(category) {
            const normalized = category.trim().toLowerCase();
            if (normalized.includes('juice') || normalized.includes('liquid')) return 'Flavor';
            if (normalized.includes('pod')) return 'Color';
            return '';
        }

        function updateVariantStock() {
            const total = [...variantList.querySelectorAll('input[name$="[stock]"]')]
                .reduce((sum, input) => sum + (Number(input.value) || 0), 0);
            stockInput.value = total;
        }

        function addVariantRow(name = '', stock = 0) {
            const index = Math.max(-1, ...[...variantList.querySelectorAll('input[name^="variants["]')]
                .map(input => Number(input.name.match(/^variants\[(\d+)\]/)?.[1] ?? -1))) + 1;
            const label = variantEditor.dataset.label;
            const row = document.createElement('div');
            row.className = 'variant-row';

            const nameLabel = document.createElement('label');
            const nameCaption = document.createElement('span');
            nameCaption.className = 'variant-row-label';
            nameCaption.textContent = label;
            const nameInput = document.createElement('input');
            nameInput.name = `variants[${index}][name]`;
            nameInput.value = name;
            nameInput.maxLength = 100;
            nameInput.placeholder = label === 'Flavor' ? 'e.g. Blueberry Ice' : 'e.g. Midnight Black';
            nameInput.required = true;
            nameLabel.append(nameCaption, nameInput);

            const stockLabel = document.createElement('label');
            const stockCaption = document.createElement('span');
            stockCaption.textContent = 'Stock';
            const stockInput = document.createElement('input');
            stockInput.type = 'number';
            stockInput.name = `variants[${index}][stock]`;
            stockInput.value = stock;
            stockInput.min = 0;
            stockInput.step = 1;
            stockInput.required = true;
            stockLabel.append(stockCaption, stockInput);

            const imageLabel = document.createElement('label');
            imageLabel.className = 'variant-image-picker';
            const imageCaption = document.createElement('span');
            imageCaption.className = 'variant-image-label';
            imageCaption.textContent = 'Option image';
            const imagePreview = document.createElement('span');
            imagePreview.className = 'variant-image-preview';
            imagePreview.setAttribute('aria-hidden', 'true');
            imagePreview.textContent = '+';
            const imageInput = document.createElement('input');
            imageInput.className = 'variant-image-input';
            imageInput.type = 'file';
            imageInput.name = `variants[${index}][image]`;
            imageInput.accept = 'image/*';
            imageLabel.append(imageCaption, imagePreview, imageInput);

            const removeButton = document.createElement('button');
            removeButton.className = 'remove-variant';
            removeButton.type = 'button';
            removeButton.setAttribute('aria-label', 'Remove option');
            removeButton.textContent = '×';
            row.append(nameLabel, stockLabel, imageLabel, removeButton);
            variantList.append(row);
        }

        function syncVariantEditor() {
            const label = optionLabel(categoryInput.value);
            variantEditor.hidden = !label;
            variantEditor.dataset.label = label;
            document.getElementById('variant-label').textContent = label;
            variantList.querySelectorAll('.variant-row-label').forEach(element => element.textContent = label);
            variantList.querySelectorAll('input').forEach(input => input.disabled = !label);
            stockInput.readOnly = Boolean(label);
            document.getElementById('stock-label').textContent = label ? 'Overall stock' : 'Stock quantity';
            document.getElementById('stock-hint').hidden = !label;
            if (label && !variantList.children.length) addVariantRow();
            if (label) updateVariantStock();
        }

        function syncCategory() {
            const isCustom = categorySelect.value === 'custom';
            customCategoryField.hidden = !isCustom;
            customCategoryInput.required = isCustom;
            categoryInput.value = isCustom ? customCategoryInput.value.trim() : categorySelect.value;
            syncVariantEditor();
        }

        categorySelect.addEventListener('change', syncCategory);
        customCategoryInput.addEventListener('input', syncCategory);
        addVariantButton.addEventListener('click', () => addVariantRow());
        variantList.addEventListener('input', updateVariantStock);
        variantList.addEventListener('change', event => {
            const input = event.target.closest('.variant-image-input');
            const [file] = input?.files ?? [];
            if (!file) return;

            const preview = input.closest('.variant-image-picker').querySelector('.variant-image-preview');
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = 'Selected option image preview';
            preview.replaceChildren(image);
        });
        variantList.addEventListener('click', event => {
            if (!event.target.closest('.remove-variant')) return;
            event.target.closest('.variant-row').remove();
            updateVariantStock();
            if (!variantList.children.length && !variantEditor.hidden) addVariantRow();
        });
        syncCategory();

        openDeleteConfirm?.addEventListener('click', () => {
            deleteConfirmDialog.showModal();
            confirmProductDelete.focus();
        });

        confirmProductDelete?.addEventListener('click', () => {
            document.getElementById('delete-product').requestSubmit();
        });

        deleteConfirmDialog?.addEventListener('click', event => {
            if (event.target === deleteConfirmDialog) deleteConfirmDialog.close();
        });
    </script>
@endpush
