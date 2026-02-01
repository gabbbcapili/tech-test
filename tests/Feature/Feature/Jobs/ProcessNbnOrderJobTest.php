<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessNbnOrderJob;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessNbnOrderJobTest extends TestCase {
    use RefreshDatabase;

    public function it_marks_application_as_complete_and_stores_order_id_on_successful_order() {
        $stub = json_decode(
            file_get_contents(base_path('tests/stubs/nbn-successful-response.json')),
            true
        );

        Http::fake([
            '*' => Http::response($stub, 200),
        ]);

        $application = Application::factory()->create([
            'plan_type' => 'nbn',
            'status'    => 'order',
        ]);

        (new ProcessNbnOrderJob($application))->handle();

        $application->refresh();

        $expectedOrderId = $stub['order_id'] ?? $stub['orderId'] ?? null;

        $this->assertEquals('complete', $application->status);
        $this->assertEquals($expectedOrderId, $application->order_id);
    }

    public function it_marks_application_as_order_failed_when_b2b_api_returns_failure() {
        $stub = json_decode(
            file_get_contents(base_path('tests/stubs/nbn-fail-response.json')),
            true
        );

        Http::fake([
            '*' => Http::response($stub, 400),
        ]);

        $application = Application::factory()->create([
            'plan_type' => 'nbn',
            'status'    => 'order',
        ]);

        (new ProcessNbnOrderJob($application))->handle();

        $application->refresh();

        $this->assertEquals('order_failed', $application->status);
        $this->assertNull($application->order_id);
    }

    public function it_marks_application_as_order_failed_when_an_exception_occurs() {
        // Simulate a 500 error or forced exception
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $application = Application::factory()->create([
            'plan_type' => 'nbn',
            'status'    => 'order',
        ]);

        (new ProcessNbnOrderJob($application))->handle();

        $application->refresh();

        $this->assertEquals('order_failed', $application->status);
    }

    public function it_ignores_non_nbn_or_non_order_applications() {
        // We don't expect any HTTP requests at all in this scenario
        Http::fake(); // no Http::response() here

        $application = Application::factory()->create([
            'plan_type' => 'mobile', // non-nbn
            'status'    => 'order',
        ]);

        (new ProcessNbnOrderJob($application))->handle();

        $application->refresh();

        $this->assertEquals('order', $application->status);
        $this->assertNull($application->order_id);

        Http::assertNothingSent();
    }
}
