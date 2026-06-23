<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_addresses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/user/addresses', [
            'name' => 'Home',
            'description' => 'Main address',
            'location_lat' => '33.510414',
            'location_lng' => '36.278336',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Home');

        $listResponse = $this->actingAs($user, 'sanctum')->getJson('/api/user/addresses');

        $listResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Home']);
    }
}
