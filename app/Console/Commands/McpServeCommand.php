<?php

namespace App\Console\Commands;

use App\Mcp\Support\LocalUserResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class McpServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:serve {--user= : The email of the user the server acts as}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the TaskFlow MCP server over stdio as a given user';

    /**
     * Execute the console command.
     */
    public function handle(LocalUserResolver $resolver): int
    {
        $email = $this->option('user');

        try {
            $user = $resolver->resolve(is_string($email) ? $email : null);
        } catch (InvalidArgumentException $e) {
            // stdout carries the JSON-RPC stream, so diagnostics go to stderr.
            $this->output->getErrorStyle()->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        Auth::setUser($user);

        $this->output->getErrorStyle()->writeln(
            "<info>TaskFlow MCP server running as {$user->email}.</info>",
        );

        return $this->call('mcp:start', ['handle' => 'taskflow']);
    }
}
