<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReceive extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
