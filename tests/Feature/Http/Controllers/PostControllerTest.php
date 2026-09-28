<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('returns paginated posts with their related users', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $firstPost = Post::factory()->for($firstUser)->create();
    $secondPost = Post::factory()->for($secondUser)->create();

    $response = $this->getJson('/api/posts');

    $response->assertOk()
        ->assertJsonStructure([
            'current_page',
            'data' => [['id', 'title', 'body', 'user' => ['id', 'name', 'email']]],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ])
        ->assertJsonPath('total', 2)
        ->assertJsonPath('per_page', 15);

    $posts = collect($response->json('data'))->keyBy('id');

    expect($posts[$firstPost->id]['user']['email'])->toBe($firstUser->email)
        ->and($posts[$secondPost->id]['user']['email'])->toBe($secondUser->email);
});

test('creates a post for the authenticated user and ignores the submitted user id', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/posts', [
        'title' => 'A new post',
        'body' => 'A useful post body.',
        'user_id' => $anotherUser->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('title', 'A new post')
        ->assertJsonPath('user.id', $user->id);

    $this->assertDatabaseHas('posts', [
        'title' => 'A new post',
        'body' => 'A useful post body.',
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseMissing('posts', [
        'title' => 'A new post',
        'user_id' => $anotherUser->id,
    ]);
});

test('forbids another user from updating a post', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->for($owner)->create([
        'title' => 'Original title',
        'body' => 'Original body.',
    ]);
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson("/api/posts/{$post->id}", [
        'title' => 'Changed title',
        'body' => 'Changed body.',
    ])->assertForbidden();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Original title',
        'body' => 'Original body.',
    ]);
});

test('allows the owner to delete a post', function () {
    $owner = User::factory()->create();
    $post = Post::factory()->for($owner)->create();
    Sanctum::actingAs($owner);

    $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});
