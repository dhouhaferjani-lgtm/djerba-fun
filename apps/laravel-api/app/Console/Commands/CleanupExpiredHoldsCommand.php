<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\HoldStatus;
use App\Models\BookingHold;
use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredHoldsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:cleanup-holds
                            {--dry-run : Show what would be cleaned up without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired booking holds and carts, releasing capacity back to slots';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->cleanupExpiredHolds($dryRun);
        $this->cleanupExpiredCarts($dryRun);

        return Command::SUCCESS;
    }

    /**
     * Cleanup expired booking holds.
     */
    protected function cleanupExpiredHolds(bool $dryRun): void
    {
        $expiredHolds = BookingHold::expired()->with('slot')->get();

        $this->info("Found {$expiredHolds->count()} expired holds");

        $cleaned = 0;
        foreach ($expiredHolds as $hold) {
            if ($dryRun) {
                $this->line("  Would expire hold {$hold->id} (slot: {$hold->slot_id}, qty: {$hold->quantity})");
            } else {
                try {
                    $hold->expire();
                    $cleaned++;
                } catch (\Throwable $e) {
                    Log::error('Failed to cleanup expired hold', [
                        'hold_id' => $hold->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed to cleanup hold {$hold->id}: {$e->getMessage()}");
                }
            }
        }

        if (!$dryRun) {
            $this->info("Cleaned up {$cleaned} expired holds");
        }
    }

    /**
     * Cleanup expired carts.
     */
    protected function cleanupExpiredCarts(bool $dryRun): void
    {
        $expiredCarts = Cart::expired()->with(['items.hold'])->get();

        $this->info("Found {$expiredCarts->count()} expired carts");

        $cleaned = 0;
        foreach ($expiredCarts as $cart) {
            if ($dryRun) {
                $itemCount = $cart->items->count();
                $this->line("  Would abandon cart {$cart->id} ({$itemCount} items)");
            } else {
                try {
                    // Expire all holds in the cart
                    foreach ($cart->items as $item) {
                        if ($item->hold && $item->hold->status === HoldStatus::ACTIVE) {
                            $item->hold->expire();
                        }
                    }

                    // Mark cart as abandoned
                    $cart->abandon();
                    $cleaned++;
                } catch (\Throwable $e) {
                    Log::error('Failed to cleanup expired cart', [
                        'cart_id' => $cart->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed to cleanup cart {$cart->id}: {$e->getMessage()}");
                }
            }
        }

        if (!$dryRun) {
            $this->info("Cleaned up {$cleaned} expired carts");
        }
    }
}
