<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoldReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_no',
        'customer_id',
        'receipt_type',
        'receipt_date',
        'total_gross_weight',
        'total_khalis_weight',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_gross_weight' => 'float',
        'total_khalis_weight' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoldReceiptItem::class, 'receipt_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFormattedReceiptNoAttribute(): string
    {
        return 'RCV-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->receipt_date->format('d/m/Y');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->receipt_type) {
            'dukandar' => 'Dukandar',
            'karigar' => 'Karigar',
            default => 'Customer',
        };
    }

    public function scopeActive($query)
    {
        return $query;
    }
}
