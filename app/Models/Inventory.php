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
     * Calculate closing balance with proper formula
     * 
     * Closing = Opening + Total Received (Receipts + Invoice Receives) - Given (Effective Gold from Invoices)
     */
    public function calculateClosingBalance(): float
    {
        $opening = (float) ($this->opening_balance ?? 0);
        $received = (float) ($this->received ?? 0);
        $given = (float) ($this->given_invoices ?? 0);
        
        return round($opening + $received - $given, 3);
    }

    /**
     * Format weight for display
     */
    public function formatWeight($value): string
    {
        return number_format((float) $value, 3, '.', ',') . ' g';
    }
}
