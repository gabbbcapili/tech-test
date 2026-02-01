<?php

namespace Tests\Feature\Console;

use App\Jobs\ProcessNbnOrderJob;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessNbnOrdersCommandTest extends TestCase {
    use RefreshDatabase;

    public function it_dispatches_jobs_for_nbn_applications_with_order_status() {
        Bus::fake();

        $nbnOrder     = Application::factory()->create(['plan_type' => 'nbn', 'status' => 'order']);
        $nbnComplete  = Application::factory()->create(['plan_type' => 'nbn', 'status' => 'complete']);
        $mobileOrder  = Application::factory()->create(['plan_type' => 'mobile', 'status' => 'order']);

        $this->artisan('applications:process-nbn-orders')
            ->assertExitCode(0);

        Bus::assertDispatched(ProcessNbnOrderJob::class, function (ProcessNbnOrderJob $job) use ($nbnOrder) {
            return $job->application->is($nbnOrder);
        });

        Bus::assertNotDispatched(ProcessNbnOrderJob::class, function (ProcessNbnOrderJob $job) use ($nbnComplete, $mobileOrder) {
            return $job->application->is($nbnComplete) || $job->application->is($mobileOrder);
        });
    }
}
