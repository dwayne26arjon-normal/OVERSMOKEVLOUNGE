<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundEvidence extends Model
{
    protected $table = 'refund_evidences';

    protected $fillable = [
        'purpose',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class);
    }
}
