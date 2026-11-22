<?php

namespace App\Support;

class DeliveryAddress
{
    private const FIELDS = ['street', 'barangay', 'city', 'province', 'postal_code'];

    public static function hasComponents(array $data): bool
    {
        foreach (self::FIELDS as $field) {
            if (blank($data[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public static function format(array $data): string
    {
        return collect(self::FIELDS)
            ->map(fn (string $field) => trim((string) $data[$field]))
            ->implode(', ');
    }
}
