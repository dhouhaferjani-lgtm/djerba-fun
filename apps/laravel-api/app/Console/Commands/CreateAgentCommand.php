<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class CreateAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:create
                            {name : The name of the agent}
                            {--permissions=* : List of permissions (e.g., listings:read bookings:create)}
                            {--rate-limit=60 : Rate limit per minute}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API agent with credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $permissions = $this->option('permissions');
        $rateLimit = (int) $this->option('rate-limit');

        // Generate credentials
        $credentials = Agent::generateCredentials();

        // Create agent
        $agent = Agent::create([
            'name' => $name,
            'api_key' => $credentials['api_key_hashed'],
            'api_secret' => $credentials['api_secret_encrypted'],
            'permissions' => empty($permissions) ? ['*'] : $permissions,
            'rate_limit' => $rateLimit,
            'is_active' => true,
        ]);

        $this->info('Agent created successfully!');
        $this->newLine();

        // Display credentials (only time they'll be shown in plain text)
        $this->line('======================================');
        $this->line('AGENT CREDENTIALS');
        $this->line('======================================');
        $this->line('Agent ID: ' . $agent->id);
        $this->line('Name: ' . $agent->name);
        $this->newLine();
        $this->warn('API Key: ' . $credentials['api_key']);
        $this->warn('API Secret: ' . $credentials['api_secret']);
        $this->newLine();
        $this->line('Permissions: ' . implode(', ', $agent->permissions));
        $this->line('Rate Limit: ' . $agent->rate_limit . ' requests/minute');
        $this->line('======================================');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT: Save these credentials securely!');
        $this->warn('⚠️  The API secret will NOT be shown again.');

        return Command::SUCCESS;
    }
}
