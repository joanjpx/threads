<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ThreadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_thread()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->postJson('/api/threads', [
                             'title' => 'Test Thread',
                             'body' => 'This is a test body.',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('title', 'Test Thread');

        $this->assertDatabaseHas('threads', [
            'title' => 'Test Thread',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_list_threads()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $thread = Thread::create([
            'user_id' => $user->id,
            'title' => 'Existing Thread',
            'body' => 'Body content here',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->getJson('/api/threads');

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Existing Thread']);
    }

    public function test_user_can_update_their_own_thread()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $thread = Thread::create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'body' => 'Old Body',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                         ->putJson("/api/threads/{$thread->id}", [
                             'title' => 'New Title',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('title', 'New Title');

        $this->assertDatabaseHas('threads', ['title' => 'New Title']);
    }

    public function test_user_cannot_update_others_thread()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token2 = JWTAuth::fromUser($user2);

        $thread = Thread::create([
            'user_id' => $user1->id,
            'title' => 'User1 Title',
            'body' => 'User1 Body',
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token2"])
                         ->putJson("/api/threads/{$thread->id}", [
                             'title' => 'Hacked Title',
                         ]);

        $response->assertStatus(403);
    }
}
