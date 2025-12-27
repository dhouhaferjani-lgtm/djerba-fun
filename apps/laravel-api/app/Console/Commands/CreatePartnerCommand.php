<?php

namespace App\Console\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;

class CreatePartnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partner:create
                            {name : The name of the partner}
                            {--company= : Company name}
                            {--permissions=* : List of permissions (e.g., listings:read bookings:create)}
                            {--rate-limit=60 : Rate limit per minute}
                            {--tier=standard : Partner tier (standard, premium, enterprise)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API partner with credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $companyName = $this->option('company') ?? $name;
        $permissions = $this->option('permissions');
        $rateLimit = (int) $this->option('rate-limit');
        $tier = $this->option('tier');

        // Generate credentials
        $credentials = Partner::generateCredentials();

        // Create partner
        $partner = Partner::create([
            'name' => $name,
            'company_name' => $companyName,
            'api_key' => $credentials['api_key_hashed'],
            'api_secret' => $credentials['api_secret_encrypted'],
            'permissions' => empty($permissions) ? ['*'] : $permissions,
            'rate_limit' => $rateLimit,
            'partner_tier' => $tier,
            'kyc_status' => 'pending',
            'is_active' => true,
            'sandbox_mode' => true,
        ]);

        $this->info('Partner created successfully!');
        $this->newLine();

        // Display credentials (only time they'll be shown in plain text)
        $this->line('======================================');
        $this->line('PARTNER CREDENTIALS');
        $this->line('======================================');
        $this->line('Partner ID: ' . $partner->id);
        $this->line('Name: ' . $partner->name);
        $this->line('Company: ' . $partner->company_name);
        $this->line('Tier: ' . $partner->partner_tier);
        $this->newLine();
        $this->warn('API Key: ' . $credentials['api_key']);
        $this->warn('API Secret: ' . $credentials['api_secret']);
        $this->newLine();
        $this->line('Permissions: ' . implode(', ', $partner->permissions));
        $this->line('Rate Limit: ' . $partner->rate_limit . ' requests/minute');
        $this->line('KYC Status: ' . $partner->kyc_status);
        $this->line('Sandbox Mode: ' . ($partner->sandbox_mode ? 'YES' : 'NO'));
        $this->line('======================================');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT: Save these credentials securely!');
        $this->warn('⚠️  The API secret will NOT be shown again.');
        $this->newLine();
        $this->info('💡 Partner is in SANDBOX mode by default');
        $this->info('💡 Update KYC status to "approved" in admin panel to activate');

        return Command::SUCCESS;
    }
}
