<?php

namespace App\Console\Commands;

use App\Services\FeedGeneratorService;
use Illuminate\Console\Command;

class GenerateFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:generate
                            {--clear : Clear existing feed caches before generating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and cache product feeds';

    /**
     * Execute the console command.
     */
    public function handle(FeedGeneratorService $feedGenerator): int
    {
        $this->info('Generating product feeds...');

        if ($this->option('clear')) {
            $this->line('Clearing existing feed caches...');
            $feedGenerator->clearFeedCaches();
        }

        // Generate JSON feed
        $this->line('Generating listings JSON feed...');
        $jsonFeed = $feedGenerator->generateListingsJsonFeed();
        $this->info('✓ JSON feed generated: ' . $jsonFeed['count'] . ' listings');

        // Generate CSV feed
        $this->line('Generating listings CSV feed...');
        $csvFeed = $feedGenerator->generateListingsCsvFeed();
        $csvLines = substr_count($csvFeed, "\n") - 1; // Subtract header
        $this->info('✓ CSV feed generated: ' . $csvLines . ' listings');

        // Generate availability feed
        $this->line('Generating availability JSON feed...');
        $availabilityFeed = $feedGenerator->generateAvailabilityJsonFeed();
        $this->info('✓ Availability feed generated: ' . $availabilityFeed['count'] . ' listings with availability');

        $this->newLine();
        $this->info('All feeds generated successfully!');
        $this->line('Feeds are cached for 5 minutes.');

        return Command::SUCCESS;
    }
}
