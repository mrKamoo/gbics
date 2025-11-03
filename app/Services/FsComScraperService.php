<?php

namespace App\Services;

use App\Models\FsComProduct;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class FsComScraperService
{
    protected $client;
    protected $baseUrl = 'https://www.fs.com';

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false,
            'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => 'https://www.fs.com/fr/',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Scrape all products from FS.com catalog
     */
    public function syncAll()
    {
        Log::info('Starting FS.com catalog synchronization');

        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        // Scrape GBICs
        $gbicStats = $this->scrapeCategory('gbic', '/fr/c/1-2.5g-modules-57');
        $stats['total'] += $gbicStats['total'];
        $stats['created'] += $gbicStats['created'];
        $stats['updated'] += $gbicStats['updated'];
        $stats['errors'] += $gbicStats['errors'];

        // Scrape Patch Cords
        $patchStats = $this->scrapeCategory('patch_cord', '/fr/c/fiber-optic-cables-209');
        $stats['total'] += $patchStats['total'];
        $stats['created'] += $patchStats['created'];
        $stats['updated'] += $patchStats['updated'];
        $stats['errors'] += $patchStats['errors'];

        Log::info('FS.com catalog synchronization completed', $stats);

        return $stats;
    }

    /**
     * Scrape a specific category
     */
    public function scrapeCategory(string $category, string $url)
    {
        Log::info("Scraping category: {$category}");

        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            $fullUrl = $this->baseUrl . $url;
            $response = $this->client->get($fullUrl);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Example: scrape product listings
            // Note: This is a simplified example. Real scraping would need to be adapted
            // to FS.com's actual HTML structure
            $crawler->filter('.product-item')->each(function (Crawler $node) use ($category, &$stats) {
                try {
                    $stats['total']++;

                    // Extract product information
                    $sku = $this->extractText($node, '.product-sku');
                    $name = $this->extractText($node, '.product-name');
                    $price = $this->extractPrice($node, '.product-price');
                    $productUrl = $this->extractAttribute($node, '.product-link', 'href');
                    $imageUrl = $this->extractAttribute($node, '.product-image img', 'src');

                    if (!$sku || !$name) {
                        return; // Skip if essential data is missing
                    }

                    // Scrape detailed product page if URL is available
                    $description = null;
                    $specifications = null;
                    if ($productUrl) {
                        $details = $this->scrapeProductDetails($productUrl);
                        $description = $details['description'] ?? null;
                        $specifications = $details['specifications'] ?? null;
                    }

                    // Upsert product
                    $product = FsComProduct::updateOrCreate(
                        ['sku' => $sku],
                        [
                            'fs_com_id' => $this->generateFsComId($sku),
                            'name' => $name,
                            'category' => $category,
                            'description' => $description,
                            'specifications' => $specifications,
                            'price' => $price,
                            'currency' => 'USD',
                            'url' => $productUrl ? $this->baseUrl . $productUrl : null,
                            'image_url' => $imageUrl ? $this->normalizeImageUrl($imageUrl) : null,
                            'last_synced_at' => now(),
                        ]
                    );

                    if ($product->wasRecentlyCreated) {
                        $stats['created']++;
                    } else {
                        $stats['updated']++;
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Error scraping product: {$e->getMessage()}");
                }
            });

        } catch (\Exception $e) {
            Log::error("Error scraping category {$category}: {$e->getMessage()}");
            $stats['errors']++;
        }

        return $stats;
    }

    /**
     * Scrape product details from individual product page
     */
    protected function scrapeProductDetails(string $url)
    {
        try {
            $fullUrl = $this->baseUrl . $url;
            $response = $this->client->get($fullUrl);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $description = $this->extractText($crawler, '.product-description');

            // Extract specifications
            $specifications = [];
            $crawler->filter('.spec-table tr')->each(function (Crawler $row) use (&$specifications) {
                $label = $this->extractText($row, 'td:first-child');
                $value = $this->extractText($row, 'td:last-child');
                if ($label && $value) {
                    $specifications[$label] = $value;
                }
            });

            return [
                'description' => $description,
                'specifications' => !empty($specifications) ? json_encode($specifications) : null,
            ];

        } catch (\Exception $e) {
            Log::warning("Error scraping product details: {$e->getMessage()}");
            return [
                'description' => null,
                'specifications' => null,
            ];
        }
    }

    /**
     * Extract text from a CSS selector
     */
    protected function extractText(Crawler $crawler, string $selector)
    {
        try {
            $node = $crawler->filter($selector);
            if ($node->count() > 0) {
                return trim($node->text());
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        return null;
    }

    /**
     * Extract attribute from a CSS selector
     */
    protected function extractAttribute(Crawler $crawler, string $selector, string $attribute)
    {
        try {
            $node = $crawler->filter($selector);
            if ($node->count() > 0) {
                return $node->attr($attribute);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        return null;
    }

    /**
     * Extract and parse price
     */
    protected function extractPrice(Crawler $crawler, string $selector)
    {
        $priceText = $this->extractText($crawler, $selector);
        if ($priceText) {
            // Remove currency symbols and parse
            $price = preg_replace('/[^0-9.]/', '', $priceText);
            return $price ? (float) $price : null;
        }
        return null;
    }

    /**
     * Generate FS.com ID from SKU
     */
    protected function generateFsComId(string $sku)
    {
        return 'FSCOM-' . strtoupper($sku);
    }

    /**
     * Normalize image URL
     */
    protected function normalizeImageUrl(string $url)
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        return $this->baseUrl . $url;
    }

    /**
     * Get statistics about synced products
     */
    public function getStats()
    {
        return [
            'total_products' => FsComProduct::count(),
            'by_category' => FsComProduct::select('category')
                ->selectRaw('count(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
            'last_sync' => FsComProduct::max('last_synced_at'),
        ];
    }
}
