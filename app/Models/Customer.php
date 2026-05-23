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
     * Get current balance by calculating chronological transaction flow
     * Including invoices and receipts in proper date order
     * Receipts reduce balance (customer gave gold)
     */
    public function getCurrentBalance(): float
    {
        $balance = $this->opening_balance ?? 0;

        // Get all active invoices ordered by date
        $invoices = $this->invoices()
            ->where('status', 'active')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        // Get all receipts ordered by date
        $receipts = $this->goldReceipts()
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get();

        // Merge both into single chronological list with type indicator
        $transactions = [];
        foreach ($invoices as $invoice) {
            $transactions[] = [
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'id' => $invoice->id,
                'amount' => $invoice->effective_gold - $invoice->total_received_khalis,
                'object' => $invoice,
            ];
        }
        foreach ($receipts as $receipt) {
            $transactions[] = [
                'type' => 'receipt',
                'date' => $receipt->receipt_date,
                'id' => $receipt->id,
                'amount' => -$receipt->total_khalis_weight,
                'object' => $receipt,
            ];
        }

        // Sort by date, then by id for consistent ordering
        usort($transactions, function ($a, $b) {
            $dateCompare = $a['date']->compare($b['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return $a['id'] <=> $b['id'];
        });

        // Calculate running balance
        foreach ($transactions as $txn) {
            $balance += $txn['amount'];
        }

        return round($balance, 3);
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
