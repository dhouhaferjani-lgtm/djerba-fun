<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CurrencyConversionService;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:update
                            {--api-key= : Exchange rate API key (optional, will use config if not provided)}
                            {--force : Force update even if recent rates exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from external API with PPP adjustments';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyConversionService $conversionService): int
    {
        $this->info('🔄 Updating exchange rates...');

        try {
            // Check if we should skip (unless forced)
            if (! $this->option('force')) {
                $latestEur = $conversionService->getLatestRateInfo('EUR');

                if ($latestEur && now()->diffInHours($latestEur['updated_at']) < 12) {
                    $this->warn("⏭️  Rates were updated {$latestEur['updated_at']}. Use --force to update anyway.");

                    return Command::SUCCESS;
                }
            }

            // Update rates from API
            $apiKey = $this->option('api-key');
            $updated = $conversionService->updateRatesFromAPI($apiKey);

            if (empty($updated)) {
                $this->error('❌ No rates were updated. Check API configuration.');

                return Command::FAILURE;
            }

            // Display results in table
            $this->newLine();
            $this->info('✅ Exchange rates updated successfully!');
            $this->newLine();

            $tableData = [];

            foreach ($updated as $currency => $data) {
                $tableData[] = [
                    'Currency' => $currency,
                    'Exchange Rate' => number_format($data['rate'], 6),
                    'PPP Adjustment' => number_format($data['ppp_adjustment'], 4),
                    'Effective Rate' => number_format($data['rate'] * $data['ppp_adjustment'], 6),
                ];
            }

            $this->table(
                ['Currency', 'Exchange Rate', 'PPP Adjustment', 'Effective Rate'],
                $tableData
            );

            $this->newLine();
            $this->info('💡 Tip: Schedule this command daily with: php artisan schedule:work');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to update exchange rates:');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
