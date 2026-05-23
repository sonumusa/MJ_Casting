<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'manual_book_no' => $this->manual_book_no,
            'invoice_date' => $this->invoice_date->format('Y-m-d'),
            'invoice_date_formatted' => $this->invoice_date->format('d/m/Y'),
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ],
            'calculations' => [
                'casting_weight' => number_format($this->casting_weight, 3),
                'waste_weight' => number_format($this->waste_weight, 3),
                'total_weight' => number_format($this->total_weight, 3),
                'ratti' => number_format($this->ratti, 3),
                'ratti_rate' => number_format($this->ratti_rate, 3),
                'male_waste' => number_format($this->male_waste, 3),
                'gold_khalis' => number_format($this->gold_khalis, 3),
                'rp_mazdori_weight' => number_format($this->rp_mazdori_weight, 3),
                'casting_mazdori_weight' => number_format($this->casting_mazdori_weight, 3),
                'effective_gold' => number_format($this->effective_gold, 3),
                'rp_rate' => number_format($this->rp_rate, 2),
                'rp_amount' => number_format($this->rp_amount, 2),
                'rp_mazdori_amount' => number_format($this->rp_mazdori_amount, 2),
                'casting_mazdori_amount' => number_format($this->casting_mazdori_amount, 2),
                'grand_total' => number_format($this->grand_total, 3),
            ],
            'payment' => [
                'wasooli' => number_format($this->wasooli, 3),
                'previous_balance' => number_format($this->previous_balance, 3),
                'remaining_balance' => number_format($this->remaining_balance, 3),
            ],
            'remarks' => $this->remarks,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
