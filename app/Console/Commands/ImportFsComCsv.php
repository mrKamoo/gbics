<?php

namespace App\Console\Commands;

use App\Services\FsComImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportFsComCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fscom:import
                            {file : Path to CSV file}
                            {--dry-run : Preview import without saving}
                            {--stop-on-error : Stop on first error instead of skipping}
                            {--show-errors : Display all errors after import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import FS.com products from CSV file';

    /**
     * Execute the console command.
     */
    public function handle(FsComImportService $importService)
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $stopOnError = $this->option('stop-on-error');
        $showErrors = $this->option('show-errors');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('FS.com CSV Import');
        $this->line('═══════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['File', $filePath],
                ['Dry Run', $dryRun ? 'Yes (no changes will be saved)' : 'No'],
                ['Stop on Error', $stopOnError ? 'Yes' : 'No (skip errors)'],
            ]
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠ DRY RUN MODE: No changes will be saved to database');
            $this->newLine();
        }

        if (!$dryRun && !$this->confirm('Proceed with import?', true)) {
            $this->info('Import cancelled.');
            return 0;
        }

        try {
            $this->info('Processing CSV file...');
            $this->newLine();

            // Create progress bar
            $progressBar = $this->output->createProgressBar();
            $progressBar->setFormat(' %current% records processed | %elapsed:6s% elapsed');
            $progressBar->start();

            // Import with callback for progress
            $options = [
                'dry_run' => $dryRun,
                'skip_errors' => !$stopOnError,
            ];

            $result = $importService->importFromCsv($filePath, $options);

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($result['stats'], $result['errors'], $showErrors);

            // Success or partial success
            if ($result['stats']['errors'] === 0) {
                $this->newLine();
                $this->info('✓ Import completed successfully!');
                return 0;
            } else {
                $this->newLine();
                $this->warn("⚠ Import completed with {$result['stats']['errors']} error(s)");
                return $result['stats']['errors'] > 0 ? 1 : 0;
            }

        } catch (\Exception $e) {
            $this->newLine(2);
            $this->error('✗ Import failed: ' . $e->getMessage());

            if ($this->output->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Display import results
     */
    protected function displayResults(array $stats, array $errors, bool $showErrors): void
    {
        $this->info('Import Results');
        $this->line('───────────────────────────────────────');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total records processed', $stats['total']],
                ['Products created', $this->formatStat($stats['created'], 'success')],
                ['Products updated', $this->formatStat($stats['updated'], 'info')],
                ['Records skipped', $stats['skipped']],
                ['Errors', $this->formatStat($stats['errors'], 'error')],
            ]
        );

        // Display errors if requested
        if ($showErrors && !empty($errors)) {
            $this->newLine();
            $this->error('Errors Details:');
            $this->line('───────────────────────────────────────');

            foreach ($errors as $error) {
                $this->newLine();
                $this->line("Line {$error['line']}:");
                foreach ($error['errors'] as $errorMsg) {
                    $this->line("  • {$errorMsg}");
                }

                if (!empty($error['data'])) {
                    $this->line("  Data: " . json_encode($error['data'], JSON_UNESCAPED_UNICODE));
                }
            }
        } elseif ($stats['errors'] > 0) {
            $this->newLine();
            $this->line("Use --show-errors to see detailed error information");
        }
    }

    /**
     * Format statistic with color
     */
    protected function formatStat(int $value, string $type): string
    {
        if ($value === 0) {
            return (string)$value;
        }

        return match($type) {
            'success' => "<fg=green>{$value}</>",
            'info' => "<fg=cyan>{$value}</>",
            'error' => "<fg=red>{$value}</>",
            default => (string)$value,
        };
    }
}
