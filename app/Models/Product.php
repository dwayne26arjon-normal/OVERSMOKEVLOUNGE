<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'category',
        'description',
        'price',
        'stock',
        'image_path',
        'featured_image_path',
        'is_active',
        'is_featured',
        'is_hot',
        'is_best_seller',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_hot' => 'boolean',
            'is_best_seller' => 'boolean',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function optionLabel(): ?string
    {
        $category = mb_strtolower(trim($this->category));

        if (str_contains($category, 'juice') || str_contains($category, 'liquid')) {
            return 'Flavor';
        }

        if (str_contains($category, 'pod')) {
            return 'Color';
        }

        return null;
    }
}
