<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class PostController extends Controller
{
    public function index(): LengthAwarePaginator
    {
        return Post::with('user')->latest()->paginate(15);
    }

    public function show(Post $post): Post
    {
        return $post->load('user');
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->validated());

        return response()->json($post->load('user'), 201);
    }

    public function update(UpdatePostRequest $request, Post $post): Post
    {
        abort_unless((string) $post->user_id === (string) $request->user()->getKey(), 403);

        $post->update($request->validated());

        return $post->load('user');
    }

    public function destroy(Request $request, Post $post): Response
    {
        abort_unless((string) $post->user_id === (string) $request->user()->getKey(), 403);

        $post->delete();

        return response()->noContent();
    }
}
