<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class WallApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear Redis before tests
        Redis::flushall();
    }

    public function test_user_can_follow_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/follow/{$user2->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('message', "Ahora sigues a {$user2->name}");

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user1->id,
            'followed_id' => $user2->id,
        ]);
    }

    public function test_user_cannot_follow_themselves()
    {
        $user1 = User::factory()->create();
        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson("/api/follow/{$user1->id}");

        $response->assertStatus(400);
    }

    public function test_wall_returns_threads_from_redis_feed()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $thread = Thread::factory()->create();
        
        // Simular que el worker pobló el feed en Redis
        Redis::lpush("feed:{$user->id}", $thread->id);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->getJson('/api/wall');

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => $thread->title]);
    }

    public function test_wall_returns_empty_if_no_feed()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->getJson('/api/wall');

        $response->assertStatus(200)
                 ->assertExactJson([]);
    }
}
