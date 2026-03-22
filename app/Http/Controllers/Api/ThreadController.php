<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreadController extends Controller
{
    public function index()
    {
        // Get all threads with their users
        return response()->json(Thread::with('user:id,name,email')->latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string'
        ]);

        $thread = $request->user()->threads()->create([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        // Dispatch a simple job/event to demonstrate RabbitMQ/Kafka usage
        dispatch(new \App\Jobs\ProcessThreadEvent($thread));

        return response()->json($thread, 201);
    }

    public function show(Thread $thread)
    {
        return response()->json($thread->load('user:id,name,email'));
    }

    public function update(Request $request, Thread $thread)
    {
        if ($request->user()->id !== $thread->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string'
        ]);

        $thread->update($request->only('title', 'body'));

        return response()->json($thread);
    }

    public function destroy(Request $request, Thread $thread)
    {
        if ($request->user()->id !== $thread->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $thread->delete();

        return response()->json(['message' => 'Thread deleted']);
    }
}
