<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'cnic' => $this->cnic,
            'address' => $this->address,
            'city' => $this->city,
            'opening_balance' => number_format($this->opening_balance, 3),
            'current_balance' => number_format($this->getCurrentBalance(), 3),
            'status' => $this->status,
            'total_gold_khalis' => number_format($this->getTotalGoldKhalis(), 3),
            'total_invoiced' => number_format($this->getTotalInvoiced(), 3),
            'total_wasooli' => number_format($this->getTotalWasooli(), 3),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
