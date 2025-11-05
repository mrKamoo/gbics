<?php

namespace App\Services;

use App\Models\FsComProduct;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FsComScraperService
{
    protected $client;
    protected $baseUrl;
    protected $cookieJar;
    protected $requestCount = 0;
    protected $maxRetries;
    protected $delayBetweenRequests;
    protected $userAgents;

    public function __construct()
    {
        // Load configuration
        $this->baseUrl = config('fscom.base_url', 'https://www.fs.com');
        $this->maxRetries = config('fscom.max_retries', 3);
        $this->delayBetweenRequests = config('fscom.delay_between_requests', 2000);
        $this->userAgents = config('fscom.user_agents', [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

        $this->cookieJar = new CookieJar();

        // Build client configuration
        $clientConfig = [
            'verify' => false,
            'cookies' => $this->cookieJar,
            'timeout' => config('fscom.timeout', 45),
            'connect_timeout' => 10,
            'allow_redirects' => true,
            'http_errors' => false, // Handle errors manually
        ];

        // Add proxy if configured
        if (config('fscom.proxy.enabled', false)) {
            $proxyHost = config('fscom.proxy.host');
            $proxyPort = config('fscom.proxy.port');
            $proxyUsername = config('fscom.proxy.username');
            $proxyPassword = config('fscom.proxy.password');

            if ($proxyHost && $proxyPort) {
                $proxyUrl = $proxyUsername && $proxyPassword
                    ? "http://{$proxyUsername}:{$proxyPassword}@{$proxyHost}:{$proxyPort}"
                    : "http://{$proxyHost}:{$proxyPort}";

                $clientConfig['proxy'] = [
                    'http' => $proxyUrl,
                    'https' => $proxyUrl,
                ];

                Log::info("Using proxy: {$proxyHost}:{$proxyPort}");
            }
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * Get random user agent
     */
    protected function getRandomUserAgent()
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Get request headers with anti-detection
     */
    protected function getHeaders($referer = null)
    {
        return [
            'User-Agent' => $this->getRandomUserAgent(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'max-age=0',
            'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => $referer ? 'same-origin' : 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Referer' => $referer ?? $this->baseUrl . '/fr/',
            'DNT' => '1',
        ];
    }

    /**
     * Make HTTP request with retry logic and rate limiting
     */
    protected function makeRequest($url, $referer = null)
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                // Rate limiting: add delay between requests
                if ($this->requestCount > 0) {
                    $delay = $this->delayBetweenRequests + rand(500, 1500); // Random jitter
                    usleep($delay * 1000); // Convert to microseconds
                }

                Log::info("Making request to: {$url} (attempt " . ($attempt + 1) . ")");

                $response = $this->client->get($url, [
                    'headers' => $this->getHeaders($referer)
                ]);

                $this->requestCount++;
                $statusCode = $response->getStatusCode();

                // Check for successful response
                if ($statusCode === 200) {
                    Log::info("Request successful: {$url}");
                    return $response->getBody()->getContents();
                }

                // Handle rate limiting (429) or server errors (5xx)
                if ($statusCode === 429 || $statusCode >= 500) {
                    $waitTime = pow(2, $attempt) * 5; // Exponential backoff
                    Log::warning("Got status {$statusCode}, waiting {$waitTime}s before retry");
                    sleep($waitTime);
                    $attempt++;
                    continue;
                }

                // Check for blocking (403, 401)
                if (in_array($statusCode, [403, 401])) {
                    Log::error("Access denied (status {$statusCode}): {$url}");

                    // Try to get a fresh session
                    if ($attempt === 0) {
                        $this->refreshSession();
                    }

                    $attempt++;
                    continue;
                }

                Log::error("Unexpected status code {$statusCode} for: {$url}");
                return null;

            } catch (RequestException $e) {
                Log::error("Request failed: " . $e->getMessage());
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt);
                    Log::info("Retrying in {$waitTime}s...");
                    sleep($waitTime);
                }
            }
        }

        Log::error("Failed to fetch {$url} after {$this->maxRetries} attempts");
        return null;
    }

    /**
     * Refresh session by visiting homepage
     */
    protected function refreshSession()
    {
        Log::info("Refreshing session...");

        try {
            // Clear existing cookies
            $this->cookieJar = new CookieJar();

            // Visit homepage to get fresh cookies
            $this->client->get($this->baseUrl . '/fr/', [
                'headers' => $this->getHeaders(),
                'cookies' => $this->cookieJar,
            ]);

            sleep(2); // Wait a bit after homepage visit

        } catch (\Exception $e) {
            Log::warning("Failed to refresh session: " . $e->getMessage());
        }
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
            // First, visit homepage to establish session
            if ($this->requestCount === 0) {
                $this->refreshSession();
            }

            $fullUrl = $this->baseUrl . $url;
            $html = $this->makeRequest($fullUrl, $this->baseUrl . '/fr/');

            if (!$html) {
                Log::error("Failed to fetch category page: {$fullUrl}");
                return $stats;
            }

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
            $fullUrl = str_starts_with($url, 'http') ? $url : $this->baseUrl . $url;
            $html = $this->makeRequest($fullUrl, $this->baseUrl . '/fr/');

            if (!$html) {
                Log::warning("Failed to fetch product details: {$fullUrl}");
                return [
                    'description' => null,
                    'specifications' => null,
                ];
            }

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
