<?php

namespace App\Console\Commands;

use App\Services\FsComImportService;
use Illuminate\Console\Command;

class ExportFsComCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fscom:export
                            {file? : Output file path (default: fscom_export.csv)}
                            {--category= : Filter by category (gbic or patch_cord)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export FS.com products to CSV file';

    /**
     * Execute the console command.
     */
    public function handle(FsComImportService $importService)
    {
        $filePath = $this->argument('file') ?? 'fscom_export.csv';
        $category = $this->option('category');

        // Validate category if provided
        if ($category && !in_array($category, ['gbic', 'patch_cord'])) {
            $this->error('Invalid category. Use "gbic" or "patch_cord".');
            return 1;
        }

        $this->info('Exporting FS.com products...');
        $this->newLine();

        if ($category) {
            $this->line("Filter: {$category}");
        } else {
            $this->line('Filter: All categories');
        }

        $this->newLine();

        try {
            $count = $importService->exportToCsv($filePath, $category);

            $this->info("✓ Export completed successfully!");
            $this->newLine();

            $this->table(
                ['Detail', 'Value'],
                [
                    ['File path', $filePath],
                    ['Products exported', $count],
                    ['File size', $this->formatBytes(filesize($filePath))],
                    ['Category filter', $category ?? 'None (all products)'],
                ]
            );

            if ($count === 0) {
                $this->newLine();
                $this->warn('⚠ No products found to export.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Export failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Format bytes to human readable size
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
    }
}
