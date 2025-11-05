<?php

namespace App\Services;

use App\Models\FsComProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;

class FsComImportService
{
    protected $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    protected $errors = [];

    /**
     * Import products from CSV file
     */
    public function importFromCsv(string $filePath, array $options = [])
    {
        $dryRun = $options['dry_run'] ?? false;
        $skipErrors = $options['skip_errors'] ?? true;

        Log::info("Starting CSV import from: {$filePath}", [
            'dry_run' => $dryRun,
            'skip_errors' => $skipErrors
        ]);

        // Reset stats
        $this->resetStats();

        try {
            // Load CSV file
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // First row is header

            // Get records
            $records = $csv->getRecords();

            foreach ($records as $offset => $record) {
                $this->stats['total']++;
                $lineNumber = $offset + 2; // +2 because offset starts at 0 and we skip header

                try {
                    // Validate record
                    $validation = $this->validateRecord($record, $lineNumber);

                    if ($validation['valid']) {
                        // Import record
                        if (!$dryRun) {
                            $result = $this->importRecord($record);

                            if ($result['created']) {
                                $this->stats['created']++;
                            } elseif ($result['updated']) {
                                $this->stats['updated']++;
                            } else {
                                $this->stats['skipped']++;
                            }
                        }
                    } else {
                        $this->stats['errors']++;
                        $this->errors[] = [
                            'line' => $lineNumber,
                            'errors' => $validation['errors'],
                            'data' => $record
                        ];

                        if (!$skipErrors) {
                            throw new \Exception("Validation failed at line {$lineNumber}");
                        }
                    }

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->errors[] = [
                        'line' => $lineNumber,
                        'errors' => [$e->getMessage()],
                        'data' => $record
                    ];

                    Log::error("Error importing line {$lineNumber}: " . $e->getMessage());

                    if (!$skipErrors) {
                        throw $e;
                    }
                }
            }

            Log::info('CSV import completed', $this->stats);

        } catch (\Exception $e) {
            Log::error("CSV import failed: " . $e->getMessage());
            throw $e;
        }

        return [
            'stats' => $this->stats,
            'errors' => $this->errors
        ];
    }

    /**
     * Validate a CSV record
     */
    protected function validateRecord(array $record, int $lineNumber): array
    {
        $validator = Validator::make($record, [
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'category' => 'required|in:gbic,patch_cord',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'url' => 'nullable|url|max:500',
            'image_url' => 'nullable|url|max:500',
        ], [
            'sku.required' => 'SKU is required',
            'name.required' => 'Product name is required',
            'category.required' => 'Category is required',
            'category.in' => 'Category must be either "gbic" or "patch_cord"',
            'price.numeric' => 'Price must be a number',
            'url.url' => 'Product URL must be valid',
            'image_url.url' => 'Image URL must be valid',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['valid' => true];
    }

    /**
     * Import a single record
     */
    protected function importRecord(array $record): array
    {
        // Parse specifications JSON if provided
        $specifications = null;
        if (!empty($record['specifications'])) {
            $decoded = json_decode($record['specifications'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $specifications = $decoded;
            } else {
                // If not JSON, treat as plain text
                $specifications = ['description' => $record['specifications']];
            }
        }

        // Prepare data
        $data = [
            'fs_com_id' => $this->generateFsComId($record['sku']),
            'sku' => $record['sku'],
            'name' => $record['name'],
            'category' => $record['category'],
            'description' => $record['description'] ?? null,
            'specifications' => $specifications ? json_encode($specifications) : null,
            'price' => !empty($record['price']) ? (float)$record['price'] : null,
            'currency' => $record['currency'] ?? 'USD',
            'url' => $record['url'] ?? null,
            'image_url' => $record['image_url'] ?? null,
            'last_synced_at' => now(),
        ];

        // Upsert (update or create)
        $product = FsComProduct::updateOrCreate(
            ['sku' => $record['sku']],
            $data
        );

        return [
            'created' => $product->wasRecentlyCreated,
            'updated' => !$product->wasRecentlyCreated && $product->wasChanged(),
        ];
    }

    /**
     * Generate FS.com ID from SKU
     */
    protected function generateFsComId(string $sku): string
    {
        return 'FSCOM-' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sku));
    }

    /**
     * Reset statistics
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        $this->errors = [];
    }

    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Export current products to CSV
     */
    public function exportToCsv(string $filePath, string $category = null): int
    {
        $query = FsComProduct::query();

        if ($category) {
            $query->where('category', $category);
        }

        $products = $query->orderBy('category')->orderBy('name')->get();

        $handle = fopen($filePath, 'w');

        // Write header
        fputcsv($handle, [
            'sku',
            'name',
            'category',
            'description',
            'specifications',
            'price',
            'currency',
            'url',
            'image_url'
        ]);

        // Write data
        foreach ($products as $product) {
            fputcsv($handle, [
                $product->sku,
                $product->name,
                $product->category,
                $product->description,
                $product->specifications,
                $product->price,
                $product->currency,
                $product->url,
                $product->image_url,
            ]);
        }

        fclose($handle);

        Log::info("Exported {$products->count()} products to: {$filePath}");

        return $products->count();
    }

    /**
     * Generate a template CSV file
     */
    public function generateTemplate(string $filePath): void
    {
        $handle = fopen($filePath, 'w');

        // Write header
        fputcsv($handle, [
            'sku',
            'name',
            'category',
            'description',
            'specifications',
            'price',
            'currency',
            'url',
            'image_url'
        ]);

        // Write example rows
        $examples = [
            [
                'SFP-10G-SR',
                '10GBASE-SR SFP+ Transceiver',
                'gbic',
                'SFP+ 10G 850nm 300m DOM Duplex LC MMF',
                '{"wavelength":"850nm","distance":"300m","connector":"LC","fiber_type":"MMF"}',
                '15.00',
                'USD',
                'https://www.fs.com/fr/products/11774.html',
                'https://img-en.fs.com/file/user_manual/10gbase-sr-sfp-transceiver.jpg'
            ],
            [
                'LC-LC-OM3-1M',
                'LC to LC OM3 Duplex Fiber Patch Cable',
                'patch_cord',
                'LC/UPC to LC/UPC Duplex OM3 Multimode 1m',
                '{"length":"1m","connector_a":"LC/UPC","connector_b":"LC/UPC","fiber_type":"OM3"}',
                '3.50',
                'USD',
                'https://www.fs.com/fr/products/40197.html',
                'https://img-en.fs.com/file/user_manual/lc-lc-om3-duplex-patch-cable.jpg'
            ],
        ];

        foreach ($examples as $example) {
            fputcsv($handle, $example);
        }

        fclose($handle);

        Log::info("Generated template CSV: {$filePath}");
    }
}
