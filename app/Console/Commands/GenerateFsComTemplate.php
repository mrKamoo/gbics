<?php

namespace App\Console\Commands;

use App\Services\FsComImportService;
use Illuminate\Console\Command;

class GenerateFsComTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fscom:template {file? : Output file path (default: fscom_template.csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CSV template file for importing FS.com products';

    /**
     * Execute the console command.
     */
    public function handle(FsComImportService $importService)
    {
        $filePath = $this->argument('file') ?? 'fscom_template.csv';

        $this->info('Generating FS.com CSV template...');
        $this->newLine();

        try {
            $importService->generateTemplate($filePath);

            $this->info("✓ Template generated successfully!");
            $this->newLine();

            $this->table(
                ['Detail', 'Value'],
                [
                    ['File path', $filePath],
                    ['File size', $this->formatBytes(filesize($filePath))],
                    ['Example rows', '2 (1 GBIC + 1 Patch Cord)'],
                ]
            );

            $this->newLine();
            $this->line('CSV Format:');
            $this->line('───────────────────────────────────────');
            $this->line('• sku: Product SKU (required, unique)');
            $this->line('• name: Product name (required)');
            $this->line('• category: "gbic" or "patch_cord" (required)');
            $this->line('• description: Product description (optional)');
            $this->line('• specifications: JSON or text (optional)');
            $this->line('• price: Numeric price (optional)');
            $this->line('• currency: Currency code (optional, default: USD)');
            $this->line('• url: Product URL (optional)');
            $this->line('• image_url: Image URL (optional)');

            $this->newLine();
            $this->line('Usage:');
            $this->line("  1. Edit the template: {$filePath}");
            $this->line("  2. Add your products (one per line)");
            $this->line("  3. Import: php artisan fscom:import {$filePath}");

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to generate template: ' . $e->getMessage());
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
