<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoldReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_id',
        'description',
        'gross_weight',
        'ratti_impurity',
        'khalis_weight',
    ];

    protected $casts = [
        'gross_weight' => 'float',
        'ratti_impurity' => 'float',
        'khalis_weight' => 'float',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoldReceipt::class, 'receipt_id');
    }
}
