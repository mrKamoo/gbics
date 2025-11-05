<?php

namespace App\Console\Commands;

use App\Services\FsComScraperService;
use Illuminate\Console\Command;

class TestFsComAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fscom:test {url? : URL to test (default: homepage)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test access to FS.com website and check for blocking';

    /**
     * Execute the console command.
     */
    public function handle(FsComScraperService $scraper)
    {
        $this->info('Testing FS.com access...');
        $this->newLine();

        $testUrl = $this->argument('url') ?? '/fr/';
        $baseUrl = config('fscom.base_url', 'https://www.fs.com');
        $fullUrl = $baseUrl . $testUrl;

        $this->info("Target URL: {$fullUrl}");
        $this->newLine();

        try {
            // Use reflection to call protected method
            $reflection = new \ReflectionClass($scraper);
            $method = $reflection->getMethod('makeRequest');
            $method->setAccessible(true);

            $this->info('Making request...');
            $startTime = microtime(true);

            $html = $method->invoke($scraper, $fullUrl);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($html) {
                $this->newLine();
                $this->info("✓ Success! Response received in {$duration}ms");
                $this->newLine();

                // Analyze response
                $htmlLength = strlen($html);
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Response size', number_format($htmlLength) . ' bytes'],
                        ['Response time', $duration . ' ms'],
                        ['Contains HTML', str_contains($html, '<html') ? 'Yes' : 'No'],
                        ['Contains body', str_contains($html, '<body') ? 'Yes' : 'No'],
                    ]
                );

                $this->newLine();

                // Check for common blocking indicators
                $blockingIndicators = [
                    'captcha' => str_contains(strtolower($html), 'captcha'),
                    'cloudflare' => str_contains(strtolower($html), 'cloudflare'),
                    'access denied' => str_contains(strtolower($html), 'access denied'),
                    'forbidden' => str_contains(strtolower($html), 'forbidden'),
                    'blocked' => str_contains(strtolower($html), 'blocked'),
                ];

                $blocked = array_filter($blockingIndicators);

                if (!empty($blocked)) {
                    $this->warn('⚠ Potential blocking detected:');
                    foreach ($blocked as $indicator => $value) {
                        $this->line("  - {$indicator}");
                    }
                } else {
                    $this->info('✓ No obvious blocking indicators found');
                }

                $this->newLine();

                // Show sample of content
                if ($this->confirm('Show HTML preview?', false)) {
                    $preview = substr($html, 0, 500);
                    $this->line('First 500 characters:');
                    $this->line('---');
                    $this->line($preview);
                    $this->line('---');
                }

                return 0;

            } else {
                $this->newLine();
                $this->error('✗ Failed to retrieve content');
                $this->warn('Check logs for details: storage/logs/laravel.log');
                return 1;
            }

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
