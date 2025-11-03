<?php

namespace App\Console\Commands;

use App\Services\FsComScraperService;
use Illuminate\Console\Command;

class SyncFsComProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fscom:sync {--category= : Specific category to sync (gbic or patch_cord)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize FS.com product catalog (GBICs and Patch Cords)';

    /**
     * Execute the console command.
     */
    public function handle(FsComScraperService $scraper)
    {
        $this->info('Starting FS.com catalog synchronization...');
        $this->newLine();

        $category = $this->option('category');

        if ($category && !in_array($category, ['gbic', 'patch_cord'])) {
            $this->error('Invalid category. Use "gbic" or "patch_cord".');
            return 1;
        }

        try {
            if ($category) {
                $this->info("Syncing {$category} products...");
                $stats = $this->syncCategory($scraper, $category);
            } else {
                $this->info('Syncing all products...');
                $stats = $scraper->syncAll();
            }

            $this->newLine();
            $this->displayStats($stats);

            $this->newLine();
            $this->info('✓ Synchronization completed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Synchronization failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync a specific category
     */
    protected function syncCategory(FsComScraperService $scraper, string $category)
    {
        $urls = [
            'gbic' => '/fr/c/optical-transceivers-9',
            'patch_cord' => '/fr/c/fiber-optic-cables-209',
        ];

        return $scraper->scrapeCategory($category, $urls[$category]);
    }

    /**
     * Display synchronization statistics
     */
    protected function displayStats(array $stats)
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total products processed', $stats['total']],
                ['Products created', $stats['created']],
                ['Products updated', $stats['updated']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn("⚠ {$stats['errors']} error(s) occurred during synchronization. Check the logs for details.");
        }
    }
}
