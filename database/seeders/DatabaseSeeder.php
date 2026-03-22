<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user1 = User::factory()->create([
            'name' => 'El Influencer',
            'email' => 'influencer@example.com',
            'password' => 'password',
        ]);

        $user2 = User::factory()->create([
            'name' => 'El Fan',
            'email' => 'fan@example.com',
            'password' => 'password',
        ]);

        // User 2 follows User 1
        $user2->following()->attach($user1->id);

        // Generar algunos threads para el influencer y dejarlos ya listos en Redis
        $threads = \App\Models\Thread::factory(5)->create(['user_id' => $user1->id]);
        
        // Push manually to Redis to simulate the Kafka queue for existing records
        $redisKey = "feed:{$user2->id}";
        foreach ($threads as $thread) {
            \Illuminate\Support\Facades\Redis::lpush($redisKey, $thread->id);
        }
    }
}
