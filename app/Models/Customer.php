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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function goldReceipts(): HasMany
    {
        return $this->hasMany(GoldReceipt::class);
    }

    /**
     * Get current balance including gold receipts
     * Receipts reduce the balance (customer gave gold)
     */
    public function getCurrentBalance(): float
    {
        $lastInvoiceBalance = $this->invoices()
            ->where('status', 'active')
            ->latest('invoice_date')
            ->latest('id')
            ->value('remaining_balance');

        $totalReceipts = $this->goldReceipts()->sum('total_khalis_weight');

        return ($lastInvoiceBalance ?? $this->opening_balance ?? 0) - $totalReceipts;
    }

    public function getTotalGoldKhalis(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('gold_khalis');
    }

    public function getTotalInvoiced(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('effective_gold');
    }

    public function getTotalWasooli(): float
    {
        return (float) $this->invoices()
            ->where('status', 'active')
            ->sum('wasooli');
    }

    public function getTotalReceivedKhalis(): float
    {
        return (float) $this->goldReceipts()->sum('total_khalis_weight');
    }

    public function getPartyTypeLabelAttribute(): string
    {
        return match($this->party_type) {
            'dukandar' => 'Dukandar',
            'karigar' => 'Karigar',
            default => 'Customer',
        };
    }

    public function scopeSearchable($query, $search)
    {
        return $query->where('name', 'like', "%$search%")
                     ->orWhere('phone', 'like', "%$search%");
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('party_type', $type);
    }
}
