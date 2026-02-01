<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;
use App\Models\Plan;
use App\Models\Customer;
use App\Enums\ApplicationStatus;

class ApplicationsTableSeeder extends Seeder {
public function run(): void {
        // created new seeder for functional / load testing
        // Create a few plans
        $plans = Plan::factory()
            ->count(5)
            ->create();

        // Create customers
        $customers = Customer::factory()
            ->count(20)
            ->create();

        // Create applications
        foreach (range(1, 50) as $i) {
            Application::factory()->create([
                'customer_id' => $customers->random()->id,
                'plan_id'     => $plans->random()->id,
                'status'      => $this->randomStatus(),
                'order_id'    => null,
            ]);
        }

        // Create some completed applications with order_id
        Application::factory()
            ->count(10)
            ->create([
                'customer_id' => $customers->random()->id,
                'plan_id'     => $plans->random()->id,
                'status'      => ApplicationStatus::Complete,
                'order_id'    => fn() => 'ORD-' . fake()->randomNumber(6),
            ]);

        // Create some pending NBN applications
        Application::factory()
            ->count(10)
            ->create([
                'customer_id' => $customers->random()->id,
                'plan_id'     => $plans->random()->id,
                'status'      => ApplicationStatus::Order,
            ]);
    }


    private function randomStatus(): ApplicationStatus {
        return collect([
            ApplicationStatus::Prelim,
            ApplicationStatus::PaymentRequired,
            ApplicationStatus::Order,
            ApplicationStatus::OrderFailed,
            ApplicationStatus::Complete,
        ])->random();
    }
}
