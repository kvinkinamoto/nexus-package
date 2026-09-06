<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;

/**
 * No self-service token UI yet — this is enough to issue/demonstrate a
 * Sanctum token for REST (and any installed API plugin, e.g. GraphQL) auth.
 * A real admin-panel "API tokens" screen is separate scope.
 */
class IssueApiToken extends Command
{
    protected $signature = 'nexus:api-token:issue {email}';

    protected $description = 'Issue a Sanctum personal access token for a user, for API access';

    public function handle(): int
    {
        $email = $this->argument('email');
        $modelClass = config('auth.providers.users.model');
        $user = $modelClass::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return Command::FAILURE;
        }

        $token = $user->createToken('api')->plainTextToken;

        $this->info('Token issued:');
        $this->line($token);

        return Command::SUCCESS;
    }
}
