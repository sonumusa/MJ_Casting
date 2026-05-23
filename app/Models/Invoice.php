<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'customer_id',
        'invoice_type',
        'invoice_date',
        'casting_weight',
        'waste_weight',
        'total_weight',
        'ratti',
        'ratti_rate',
        'male_waste',
        'gold_khalis',
        'total_received_khalis',
        'rp_rate',
        'rp_amount',
        'rp_mazdori_weight',
        'rp_mazdori_rate',
        'rp_mazdori_amount',
        'casting_mazdori_weight',
        'casting_mazdori_rate',
        'casting_mazdori_amount',
        'effective_gold',
        'grand_total',
        'wasooli',
        'previous_balance',
        'remaining_balance',
        'manual_book_no',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'casting_weight' => 'float',
        'waste_weight' => 'float',
        'total_weight' => 'float',
        'ratti' => 'float',
        'ratti_rate' => 'float',
        'male_waste' => 'float',
        'gold_khalis' => 'float',
        'total_received_khalis' => 'float',
        'rp_rate' => 'float',
        'rp_amount' => 'float',
        'rp_mazdori_weight' => 'float',
        'rp_mazdori_rate' => 'float',
        'rp_mazdori_amount' => 'float',
        'casting_mazdori_weight' => 'float',
        'casting_mazdori_rate' => 'float',
        'casting_mazdori_amount' => 'float',
        'effective_gold' => 'float',
        'grand_total' => 'float',
        'wasooli' => 'float',
        'previous_balance' => 'float',
        'remaining_balance' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get customer relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get receive rows (gold received from party in this invoice)
     */
    public function receives(): HasMany
    {
        return $this->hasMany(InvoiceReceive::class);
    }

    /**
     * Get creator relationship
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get updater relationship
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Format grand total for display
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        return 'Rs. ' . number_format($this->grand_total, 2, '.', ',');
    }

    /**
     * Format date for display
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->invoice_date->format('d/m/Y');
    }

    /**
     * Format invoice number
     */
    public function getFormattedInvoiceNoAttribute(): string
    {
        return 'INV-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice type label
     */
    public function getInvoiceTypeLabelAttribute(): string
    {
        return match($this->invoice_type) {
            'dukandar' => 'Dukandar',
            'karigar' => 'Karigar',
            default => 'Customer',
        };
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        $class = $this->status === 'active' ? 'bg-success' : 'bg-danger';
        $label = ucfirst($this->status);
        return "<span class='badge $class'>$label</span>";
    }

    /**
     * Active scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * For customer scope
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * By invoice type scope
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('invoice_type', $type);
    }

    /**
     * Date range scope
     */
    public function scopeForDateRange($query, $from, $to)
    {
        return $query->whereBetween('invoice_date', [$from, $to]);
    }

    /**
     * Searchable scope
     */
    public function scopeSearchable($query, $search)
    {
        return $query->where('invoice_no', 'like', "%$search%")
                     ->orWhere('manual_book_no', 'like', "%$search%")
                     ->orWhereHas('customer', function ($q) use ($search) {
                         $q->where('name', 'like', "%$search%");
                     });
    }
}
