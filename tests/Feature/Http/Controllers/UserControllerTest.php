<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('requires authentication to access the Users API', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

test('returns a user without sensitive fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson("/api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('name', $user->name)
        ->assertJsonPath('email', $user->email)
        ->assertJsonMissingPath('password')
        ->assertJsonMissingPath('remember_token');
});

test('returns a paginated user index', function () {
    $user = User::factory()->create();
    User::factory()->count(2)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonStructure([
            'current_page',
            'data' => [['id', 'name', 'email']],
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
        ->assertJsonPath('total', 3)
        ->assertJsonPath('per_page', 15);
});
