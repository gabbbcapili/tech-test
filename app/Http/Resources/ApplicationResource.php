<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource {
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array {
        return [
            'id'                => $this->id,
            'customer_full_name' => $this->customer_full_name, // accessor below
            'address'           => $this->address_1, // or format as needed
            'address_1'         => $this->address_1,
            'address_2'         => $this->address_2,
            'city'              => $this->city,
            'state'             => $this->state,
            'postcode'          => $this->postcode,
            'plan_type'         => $this->plan_type,
            'plan_name'         => $this->plan_name,
            'plan_monthly_cost' => $this->plan_monthly_cost, // accessor below
            // Only show order_id for complete applications
            'order_id'          => $this->when(
                $this->status->value === 'complete',
                $this->order_id
            ),
        ];
    }
}
