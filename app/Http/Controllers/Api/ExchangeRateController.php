<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BanxicoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class ExchangeRateController extends Controller
{
    private BanxicoService $banxicoService;

    public function __construct(BanxicoService $banxicoService)
    {
        $this->banxicoService = $banxicoService;
    }

    /**
     * Get exchange rate for a specific currency and date
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'currency' => 'required|string|size:3',
                'date' => 'required|date|date_format:Y-m-d|before_or_equal:today',
            ]);

            $currency = strtoupper($validated['currency']);
            $date = $validated['date'];

            // Check if currency is supported
            $supportedCurrencies = $this->banxicoService->getSupportedCurrencies();
            if (!in_array($currency, $supportedCurrencies)) {
                return response()->json([
                    'error' => 'Unsupported currency',
                    'message' => "Currency {$currency} is not supported. Supported currencies: " . implode(', ', $supportedCurrencies),
                    'supported_currencies' => $supportedCurrencies,
                ], 422);
            }

            // Get exchange rate
            $rateData = $this->banxicoService->getExchangeRate($currency, $date);

            return response()->json([
                'success' => true,
                'data' => $rateData,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'Invalid request parameters',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            \Log::error('Exchange rate API error: ' . $e->getMessage(), [
                'currency' => $request->get('currency'),
                'date' => $request->get('date'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Exchange rate not available',
                'message' => $e->getMessage(),
                'currency' => $request->get('currency'),
                'date' => $request->get('date'),
            ], 404);
        }
    }

    /**
     * Get supported currencies
     *
     * @return JsonResponse
     */
    public function getSupportedCurrencies(): JsonResponse
    {
        try {
            $currencies = $this->banxicoService->getSupportedCurrencies();
            
            return response()->json([
                'success' => true,
                'currencies' => $currencies,
                'count' => count($currencies),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Unable to retrieve supported currencies',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Banxico API connection
     *
     * @return JsonResponse
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->banxicoService->testConnection();
            
            return response()->json([
                'success' => $result['success'],
                'connection_test' => $result,
            ], $result['success'] ? 200 : 503);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Connection test failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear exchange rate cache
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'currency' => 'nullable|string|size:3',
                'date' => 'nullable|date|date_format:Y-m-d',
            ]);

            $currency = $request->get('currency') ? strtoupper($request->get('currency')) : null;
            $date = $request->get('date');

            $cleared = $this->banxicoService->clearCache($currency, $date);

            return response()->json([
                'success' => true,
                'message' => $cleared ? 'Cache cleared successfully' : 'Cache clear operation completed',
                'currency' => $currency,
                'date' => $date,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'Invalid request parameters',
                'errors' => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Cache clear failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}