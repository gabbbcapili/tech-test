<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model {
    use HasFactory;

    protected $fillable = [
        'customer_first_name',
        'customer_last_name',
        'address_1',
        'address_2',
        'city',
        'state',
        'postcode',
        'plan_name',
        'plan_monthly_cost_cents',
        'status',
        'order_id',
    ];

    protected $appends = [
        'customer_full_name',
        'plan_type',
        'plan_name',
        'plan_monthly_cost',
    ];

    public function getCustomerFullNameAttribute(): string {
        return trim("{$this->customer_first_name} {$this->customer_last_name}");
    }

    public function plan() {
        return $this->belongsTo(Plan::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function getPlanTypeAttribute() {
        return $this->plan->plan_type ?? null;
    }

    public function getPlanNameAttribute() {
        return $this->plan->name ?? null;
    }

    public function getPlanMonthlyCostAttribute() {
        if (!$this->plan) return null;

        return number_format($this->plan->monthly_cost / 100, 2, '.', '');
    }

    protected $casts = [
        'status' => ApplicationStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => ApplicationCreated::class,
    ];
}
