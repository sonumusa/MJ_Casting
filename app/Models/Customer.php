<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'cnic',
        'address',
        'city',
        'opening_balance',
        'status',
        'party_type',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get invoices for this customer
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get gold receipts for this customer
     */
    public function goldReceipts(): HasMany
    {
        return $this->hasMany(GoldReceipt::class);
    }

    /**
     * Get current balance for customer
     */
    public function getCurrentBalance(): float
    {
        return $this->invoices()
            ->where('status', 'active')
            ->latest('invoice_date')
            ->latest('id')
            ->value('remaining_balance') ?? $this->opening_balance;
    }

    /**
     * Get total gold khalis given to customer
     */
    public function getTotalGoldKhalis(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('gold_khalis');
    }

    /**
     * Get total invoiced amount
     */
    public function getTotalInvoiced(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('grand_total');
    }

    /**
     * Get total wasooli (payment received)
     */
    public function getTotalWasooli(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('wasooli');
    }

    /**
     * Get total gold received from this party via separate receipts
     */
    public function getTotalReceivedKhalis(): float
    {
        return (float) $this->goldReceipts()
            ->sum('total_khalis_weight');
    }

    /**
     * Get party type label
     */
    public function getPartyTypeLabelAttribute(): string
    {
        return match($this->party_type) {
            'dukandar' => 'Dukandar',
            'karigar' => 'Karigar',
            default => 'Customer',
        };
    }

    /**
     * Search scope
     */
    public function scopeSearchable($query, $search)
    {
        return $query->where('name', 'like', "%$search%")
                     ->orWhere('phone', 'like', "%$search%");
    }

    /**
     * Active scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by party type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('party_type', $type);
    }
}
