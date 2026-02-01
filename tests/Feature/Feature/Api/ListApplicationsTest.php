<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class ListApplicationsTest extends TestCase {
    use RefreshDatabase;

    public function it_returns_paginated_applications_for_an_authenticated_user() {
        $user = User::factory()->create();

        Application::factory()->count(3)->create([
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_full_name',
                        'address_1',
                        'address_2',
                        'city',
                        'state',
                        'postcode',
                        'plan_type',
                        'plan_name',
                        'plan_monthly_cost',
                        // order_id is conditional, so not enforced here
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function it_orders_applications_from_oldest_to_newest() {
        $user = User::factory()->create();

        $older = Application::factory()->create(['created_at' => Carbon::now()->subDays(5)]);
        $newer = Application::factory()->create(['created_at' => Carbon::now()->subDay()]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame($older->id, $ids[0]);
        $this->assertSame($newer->id, $ids[1]);
    }

    public function it_filters_applications_by_plan_type() {
        $user = User::factory()->create();

        $nbn    = Application::factory()->create(['plan_type' => 'nbn']);
        $mobile = Application::factory()->create(['plan_type' => 'mobile']);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/applications?plan_type=nbn');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($nbn->id));
        $this->assertFalse($ids->contains($mobile->id));
    }

    public function it_only_exposes_order_id_for_complete_applications() {
        $user = User::factory()->create();

        $complete = Application::factory()->create([
            'status'   => 'complete',
            'order_id' => 'ORD-123',
        ]);

        $inProgress = Application::factory()->create([
            'status'   => 'order',
            'order_id' => 'ORD-999',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertOk();

        $data = collect($response->json('data'))->keyBy('id');

        $this->assertEquals('ORD-123', $data[$complete->id]['order_id']);
        $this->assertArrayNotHasKey('order_id', $data[$inProgress->id]);
    }
}
