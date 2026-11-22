<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

class OrderInventory
{
    public static function restoreForCancellation(Order $order): void
    {
        $items = $order->items;
        $quantities = $items->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn ($productItems) => $productItems->sum('quantity'));

        foreach ($quantities as $productId => $quantity) {
            Product::query()->whereKey($productId)->increment('stock', $quantity);
        }

        foreach ($items->whereNotNull('product_id')->whereNotNull('color') as $item) {
            ProductVariant::query()
                ->where('product_id', $item->product_id)
                ->where('name', $item->color)
                ->increment('stock', $item->quantity);
        }
    }
}
