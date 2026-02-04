<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class BanxicoService
{
    private string $baseUrl;
    private string $token;
    private int $timeout;
    private int $retryAttempts;
    private array $currencySeries;
    private array $cacheConfig;
    private array $lookupConfig;

    public function __construct()
    {
        $this->baseUrl = config('exchange.banxico.base_url');
        $this->token = config('exchange.banxico.token');
        $this->timeout = config('exchange.banxico.timeout', 30);
        $this->retryAttempts = config('exchange.banxico.retry_attempts', 3);
        $this->currencySeries = config('exchange.currency_series', []);
        $this->cacheConfig = config('exchange.cache', []);
        $this->lookupConfig = config('exchange.lookup', []);
    }

    /**
     * Get exchange rate for a specific currency and date
     *
     * @param string $currency Currency code (e.g., 'USD')
     * @param string $date Date in Y-m-d format
     * @return array Exchange rate data
     * @throws Exception
     */
    public function getExchangeRate(string $currency, string $date): array
    {
        // Validate currency is supported
        if (!isset($this->currencySeries[$currency])) {
            throw new Exception("Currency {$currency} is not supported");
        }

        $cacheKey = $this->getCacheKey($currency, $date);
        
        // Try cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return array_merge($cached, ['cached' => true]);
        }

        // Get rate from Banxico API
        $rateData = $this->fetchFromBanxico($currency, $date);
        
        // Cache the result if successful
        if ($rateData) {
            Cache::put($cacheKey, $rateData, $this->cacheConfig['ttl'] ?? 86400);
        }

        return array_merge($rateData, ['cached' => false]);
    }

    /**
     * Fetch exchange rate from Banxico API
     *
     * @param string $currency
     * @param string $date
     * @return array
     * @throws Exception
     */
    private function fetchFromBanxico(string $currency, string $date): array
    {
        $seriesId = $this->currencySeries[$currency];
        $requestedDate = Carbon::parse($date);
        $maxDaysBack = $this->lookupConfig['max_days_back'] ?? 10;
        
        // Try to get rate for the exact date first, then look backwards
        for ($daysBack = 0; $daysBack <= $maxDaysBack; $daysBack++) {
            $searchDate = $requestedDate->copy()->subDays($daysBack);
            $formattedDate = $searchDate->format('Y-m-d');
            
            try {
                $response = Http::timeout(10) // Reducido timeout a 10 segundos
                    ->withHeaders([
                        'Bmx-Token' => $this->token,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->retry(2, 500) // Reducido reintentos
                    ->get("{$this->baseUrl}series/{$seriesId}/datos/{$formattedDate}/{$formattedDate}");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['bmx']['series'][0]['datos'][0]['dato'])) {
                        $rate = $data['bmx']['series'][0]['datos'][0]['dato'];
                        
                        // Skip if rate is "N/E" (not available)
                        if ($rate === 'N/E' || empty($rate)) {
                            continue;
                        }

                        $rateValue = (float) $rate;
                        $decimalPlaces = $this->lookupConfig['decimal_places'] ?? 4;

                        return [
                            'currency' => $currency,
                            'requested_date' => $date,
                            'effective_date' => $searchDate->format('Y-m-d'),
                            'rate' => $rateValue,
                            'rate_formatted' => number_format($rateValue, $decimalPlaces, '.', ''),
                            'source' => 'BANXICO',
                            'series_id' => $seriesId,
                            'days_back' => $daysBack,
                        ];
                    }
                } else {
                    Log::warning("Banxico API returned {$response->status()} for {$currency} on {$formattedDate}");
                }
            } catch (Exception $e) {
                Log::warning("Banxico API request failed for {$currency} on {$formattedDate}: " . $e->getMessage());
                
                // Continue to next date if this one fails
                continue;
            }
        }

        throw new Exception("No exchange rate found for {$currency} within {$maxDaysBack} days from {$date}");
    }

    /**
     * Get cache key for exchange rate
     *
     * @param string $currency
     * @param string $date
     * @return string
     */
    private function getCacheKey(string $currency, string $date): string
    {
        $prefix = $this->cacheConfig['prefix'] ?? 'banxico_rate_';
        return "{$prefix}{$currency}:{$date}";
    }

    /**
     * Clear cache for specific currency and date
     *
     * @param string $currency
     * @param string $date
     * @return bool
     */
    public function clearCache(string $currency = null, string $date = null): bool
    {
        if ($currency && $date) {
            $cacheKey = $this->getCacheKey($currency, $date);
            return Cache::forget($cacheKey);
        }

        // Clear all exchange rate cache (implement pattern matching if needed)
        return true;
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return array_keys($this->currencySeries);
    }

    /**
     * Test Banxico API connectivity
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Bmx-Token' => $this->token,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}series/SF43718/datos/" . now()->format('d/m/Y') . "/" . now()->format('d/m/Y'));

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed',
                'response' => $response->json(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status' => null,
                'message' => $e->getMessage(),
                'response' => null,
            ];
        }
    }
}