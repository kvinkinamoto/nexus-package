<?php

namespace Nodex\Nexus\commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * v1 has no self-service token UI (see Services/GraphQL/SchemaBuilder's
 * docblock on scope) — this is enough to demonstrate and test the GraphQL
 * endpoint's Sanctum auth. A real admin-panel "API tokens" screen is
 * separate scope.
 */
class IssueApiToken extends Command
{
    protected $signature = 'nexus:api-token:issue {email}';

    protected $description = 'Issue a Sanctum personal access token for a user, for GraphQL/API access';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email [{$email}].");

            return Command::FAILURE;
        }

        $token = $user->createToken('graphql')->plainTextToken;

        $this->info('Token issued:');
        $this->line($token);

        return Command::SUCCESS;
    }
}
