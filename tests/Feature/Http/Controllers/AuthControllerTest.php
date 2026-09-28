<?php

use App\Jobs\SendWelcomeEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

test('registers a user and queues a welcome email', function () {
    Queue::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'Taylor Example',
        'email' => 'taylor@example.test',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ])
        ->assertJsonPath('user.name', 'Taylor Example')
        ->assertJsonPath('user.email', 'taylor@example.test');

    $this->assertDatabaseHas('users', [
        'name' => 'Taylor Example',
        'email' => 'taylor@example.test',
    ]);
    $this->assertIsString($response->json('token'));
    $this->assertNotEmpty($response->json('token'));

    Queue::assertPushed(SendWelcomeEmail::class, fn (SendWelcomeEmail $job): bool => $job->user->email === 'taylor@example.test');
});
