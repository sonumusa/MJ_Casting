<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'opening_balance',
        'received',
        'given_invoices',
        'closing_balance',
        'period_label',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'received' => 'float',
        'given_invoices' => 'float',
        'closing_balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get who updated this inventory
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate and get closing balance
     * Formula: Opening Balance - Given
     */
    public function calculateClosingBalance(): float
    {
        return $this->opening_balance - $this->given_invoices;
    }

    /**
     * Format weight for display
     */
    public function formatWeight($value): string
    {
        return number_format($value, 3, '.', ',') . ' g';
    }
}
