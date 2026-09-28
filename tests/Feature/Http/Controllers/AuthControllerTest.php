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

test('rate limits repeated login attempts', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/login', [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/login', [
        'email' => 'missing@example.test',
        'password' => 'incorrect-password',
    ])->assertStatus(429);
});
