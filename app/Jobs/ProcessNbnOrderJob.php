<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProcessNbnOrderJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Application $application,
    ) {
    }

    public function handle(): void {
        // Safety: only handle nbn + order status
        if ($this->application->plan_type !== 'nbn' || $this->application->status !== 'order') {
            return;
        }

        try {
            $endpoint = config('services.nbn_b2b.endpoint', env('NBN_B2B_ENDPOINT'));

            $response = Http::post($endpoint, [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city'      => $this->application->city,
                'state'     => $this->application->state,
                'postcode'  => $this->application->postcode,
                'plan_name' => $this->application->plan_name,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->application->order_id = $data['order_id'] ?? $data['orderId'] ?? null;
                $this->application->status   = 'complete';
                $this->application->save();
            } else {
                $this->failOrder();
            }
        } catch (Throwable $e) {
            // Log if you want, then mark as failed
            $this->failOrder();
        }
    }

    private function failOrder(): void {
        $this->application->status = 'order_failed';
        $this->application->save();
    }
}
