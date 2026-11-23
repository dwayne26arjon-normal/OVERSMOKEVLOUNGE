<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'reviewed_by',
        'completed_by',
        'status',
        'description',
        'admin_note',
        'approved_amount',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(RefundEvidence::class);
    }
}
