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
        {--scopes=mcp:read,mcp:write : Comma separated scopes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue a personal access token for the HTTP MCP server';

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

        $scopesOption = $this->option('scopes');
        $nameOption = $this->option('name');

        $scopes = array_values(array_filter(array_map(
            trim(...),
            explode(',', is_string($scopesOption) ? $scopesOption : 'mcp:read'),
        )));

        $token = $user->createToken(is_string($nameOption) ? $nameOption : 'MCP Client', $scopes);

        $this->components->info("Token issued for {$user->email} with scopes: ".implode(', ', $scopes));
        $this->line($token->accessToken);
        $this->newLine();
        $this->components->warn('Copy it now — it cannot be shown again.');

        return self::SUCCESS;
    }
}
