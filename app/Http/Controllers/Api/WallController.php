<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Thread;
use App\Models\User;

class WallController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        // Fetch up to 10 latest thread IDs from Redis feed array
        $threadIds = Redis::lrange("feed:{$userId}", 0, 10);

        if (empty($threadIds)) {
            return response()->json([]);
        }

        // Fetch threads from DB
        $threads = Thread::whereIn('id', $threadIds)
            ->with('user:id,name,email')
            ->get()
            ->sortBy(function($thread) use ($threadIds) {
                // sort based on the original redis array
                return array_search($thread->id, $threadIds);
            })
            ->values();

        return response()->json($threads);
    }
    
    // Extra endpoint to follow users for testing fanout
    public function follow(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['error' => 'No puedes seguirte a ti mismo'], 400);
        }

        $request->user()->following()->syncWithoutDetaching([$user->id]);

        return response()->json(['message' => "Ahora sigues a {$user->name}"]);
    }
}
