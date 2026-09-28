<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('returns a user without sensitive fields', function () {
    $user = User::factory()->create();

    $this->getJson("/api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('name', $user->name)
        ->assertJsonPath('email', $user->email)
        ->assertJsonMissingPath('password')
        ->assertJsonMissingPath('remember_token');
});

test('returns a paginated user index', function () {
    User::factory()->count(3)->create();

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
