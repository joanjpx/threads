<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class KafkaConsumeCommand extends Command
{
    protected $signature = 'kafka:consume';
    protected $description = 'Consume Kafka messages for feed fanout';

    public function handle()
    {
        $this->info('Starting Kafka Consumer: feed_updates');

        $consumer = Kafka::consumer(['feed_updates'])
            ->withGroupId('feed_group')
            ->withHandler(function (ConsumerMessage $message) {
                $body = $message->getBody();
                
                // If it's an array, cast it; otherwise access via properties.
                $authorId = is_array($body) ? ($body['author_id'] ?? null) : ($body->author_id ?? null);
                $threadId = is_array($body) ? ($body['thread_id'] ?? null) : ($body->thread_id ?? null);
                
                if ($authorId && $threadId) {
                    $this->info("Fanout for Author {$authorId}, Thread {$threadId}");
                    $followers = User::find($authorId)->followers()->pluck('users.id');
                    
                    foreach ($followers as $followerId) {
                        Redis::lpush("feed:{$followerId}", $threadId);
                        Redis::ltrim("feed:{$followerId}", 0, 99); // Keep latest 100
                    }
                }
            })
            ->build();

        $consumer->consume();
    }
}
