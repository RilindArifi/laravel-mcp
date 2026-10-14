<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class McpTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:token
        {email : The email of the user the token belongs to}
        {--name=MCP Client : A label for the token}
        {--abilities=mcp:read,mcp:write : Comma separated abilities}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue a Sanctum token for the HTTP MCP server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $emailArgument = $this->argument('email');
        $email = is_string($emailArgument) ? $emailArgument : '';

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No user found with the email [{$email}].");

            return self::FAILURE;
        }

        $abilitiesOption = $this->option('abilities');
        $nameOption = $this->option('name');

        $abilities = array_values(array_filter(array_map(
            trim(...),
            explode(',', is_string($abilitiesOption) ? $abilitiesOption : 'mcp:read'),
        )));

        $token = $user->createToken(is_string($nameOption) ? $nameOption : 'MCP Client', $abilities);

        $this->components->info("Token issued for {$user->email} with abilities: ".implode(', ', $abilities));
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->components->warn('Copy it now — it cannot be shown again.');

        return self::SUCCESS;
    }
}
