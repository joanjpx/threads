<?php

namespace App\Jobs;

use App\Models\Thread;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class ProcessThreadEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $thread;

    /**
     * Create a new job instance.
     */
    public function __construct(Thread $thread)
    {
        $this->thread = $thread;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Log locally
        Log::info('Thread processed by RabbitMQ', ['thread_id' => $this->thread->id]);
        
        // Publish to Kafka for Feed Fanout
        $message = new Message(
            headers: ['action' => 'feed_fanout'],
            body: [
                'thread_id' => $this->thread->id,
                'author_id' => $this->thread->user_id,
            ]
        );

        try {
            Kafka::publish()->onTopic('feed_updates')->withMessage($message)->send();
        } catch (\Exception $e) {
            Log::error('Failed to publish to Kafka', ['error' => $e->getMessage()]);
        }
    }
}
