<?php

namespace App\Console\Commands;

use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-welcome-email {email}')]
#[Description('Queue a welcome email for a user.')]
class SendWelcomeEmailCommand extends Command
{
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user found with that email address.');

            return self::FAILURE;
        }

        SendWelcomeEmail::dispatch($user);

        $this->info("Welcome email queued for {$user->email}.");

        return self::SUCCESS;
    }
}
