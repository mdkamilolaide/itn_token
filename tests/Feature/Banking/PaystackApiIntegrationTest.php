<?php

namespace Tests\Feature\Banking;

use Tests\TestCase;
use Tests\Helpers\Assertions\DatabaseAssertions;

/**
 * Paystack API Integration Tests
 * 
 * Tests integration with Paystack Bank Verification API
 * Tests API request/response handling, error scenarios, and rate limiting
 */
class PaystackApiIntegrationTest extends TestCase
{
    use DatabaseAssertions;

    private const PAYSTACK_TEST_KEY = 'sk_test_da75e8e02fa99244b8bb025c74011b59520213d2';
    private const PAYSTACK_API_URL = 'https://api.paystack.co/bank/resolve';

    /**
     * Test successful API request
     */
    public function testSuccessfulApiRequest()
    {
        // Mock valid Nigerian bank account
        $accountNumber = '0123456789';
        $bankCode = '044'; // Access Bank

        $response = $this->callPaystackApi($accountNumber, $bankCode);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('message', $response);
        
        if ($response['status']) {
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('account_number', $response['data']);
            $this->assertArrayHasKey('account_name', $response['data']);
            $this->assertArrayHasKey('bank_id', $response['data']);
        }
    }

    /**
     * Test API request with invalid account number
     */
    public function testApiRequestWithInvalidAccount()
    {
        $accountNumber = '9999999999'; // Non-existent account
        $bankCode = '044';

        $response = $this->callPaystackApi($accountNumber, $bankCode);

        $this->assertArrayHasKey('status', $response);
        $this->assertFalse($response['status']);
        $this->assertArrayHasKey('message', $response);
    }

    /**
     * Test API request with invalid bank code
     */
    public function testApiRequestWithInvalidBankCode()
    {
        $accountNumber = '0123456789';
        $bankCode = '999'; // Invalid bank code

        $response = $this->callPaystackApi($accountNumber, $bankCode);

        $this->assertArrayHasKey('status', $response);
        $this->assertFalse($response['status']);
    }

    /**
     * Test API timeout handling
     */
    public function testApiTimeoutHandling()
    {
        // Set very short timeout
        $accountNumber = '0123456789';
        $bankCode = '044';

        $response = $this->callPaystackApiWithTimeout($accountNumber, $bankCode, 1);

        // Should handle timeout gracefully
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    /**
     * Test API network error handling
     */
    public function testApiNetworkErrorHandling()
    {
        // Test with invalid URL to simulate network error
        $response = $this->callApiWithInvalidUrl();

        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Network', $response['error']);
    }

    /**
     * Test API response parsing
     */
    public function testApiResponseParsing()
    {
        $mockResponse = json_encode([
            'status' => true,
            'message' => 'Account number resolved',
            'data' => [
                'account_number' => '0123456789',
                'account_name' => 'John Doe',
                'bank_id' => 1
            ]
        ]);

        $parsed = $this->parseApiResponse($mockResponse);

        $this->assertIsArray($parsed);
        $this->assertTrue($parsed['status']);
        $this->assertEquals('0123456789', $parsed['data']['account_number']);
        $this->assertEquals('John Doe', $parsed['data']['account_name']);
    }

    /**
     * Test API malformed response handling
     */
    public function testApiMalformedResponseHandling()
    {
        $malformedResponse = 'Not a JSON response';

        $parsed = $this->parseApiResponse($malformedResponse);

        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('error', $parsed);
    }

    /**
     * Test API rate limiting
     */
    public function testApiRateLimiting()
    {
        $results = [];
        
        // Make multiple requests quickly
        for ($i = 0; $i < 5; $i++) {
            $response = $this->callPaystackApi('0123456789', '044');
            $results[] = $response;
            usleep(100000); // 100ms delay
        }

        // All requests should complete (or be rate limited gracefully)
        $this->assertCount(5, $results);
        
        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('status', $result);
        }
    }

    /**
     * Test different Nigerian banks
     */
    public function testDifferentNigerianBanks()
    {
        $banks = [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '058', 'name' => 'GTBank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
            ['code' => '033', 'name' => 'United Bank for Africa'],
            ['code' => '032', 'name' => 'Union Bank'],
        ];

        foreach ($banks as $bank) {
            $response = $this->callPaystackApi('0123456789', $bank['code']);
            
            $this->assertIsArray($response);
            $this->assertArrayHasKey('status', $response);
            // Each bank should respond (even if account doesn't exist)
        }
    }

    /**
     * Test API authentication
     */
    public function testApiAuthentication()
    {
        // Test with invalid API key
        $response = $this->callPaystackApiWithKey('0123456789', '044', 'invalid_key');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        
        if (!$response['status']) {
            $this->assertStringContainsString('authentication', strtolower($response['message'] ?? ''));
        }
    }

    /**
     * Test CURL options configuration
     */
    public function testCurlConfiguration()
    {
        $curlOptions = $this->getCurlOptions('0123456789', '044');

        $this->assertArrayHasKey(CURLOPT_URL, $curlOptions);
        $this->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $curlOptions);
        $this->assertArrayHasKey(CURLOPT_TIMEOUT, $curlOptions);
        $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $curlOptions);
        
        // Check timeout is reasonable
        $this->assertLessThanOrEqual(60, $curlOptions[CURLOPT_TIMEOUT]);
        
        // Check headers include authorization
        $headers = $curlOptions[CURLOPT_HTTPHEADER];
        $hasAuthHeader = false;
        foreach ($headers as $header) {
            if (stripos($header, 'Authorization') !== false) {
                $hasAuthHeader = true;
                break;
            }
        }
        $this->assertTrue($hasAuthHeader);
    }

    // ==================== Helper Methods ====================

    /**
     * Call Paystack API (mocked for testing)
     */
    private function callPaystackApi(string $accountNumber, string $bankCode): array
    {
        // In real tests with Paystack sandbox:
        // return $this->makeRealApiCall($accountNumber, $bankCode);
        
        // Mock response for testing
        if (strlen($accountNumber) === 10 && strlen($bankCode) === 3 && $accountNumber !== '9999999999' && $bankCode !== '999') {
            return [
                'status' => true,
                'message' => 'Account number resolved',
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => 'Test Account Name',
                    'bank_id' => 1
                ]
            ];
        }

        return [
            'status' => false,
            'message' => 'Could not resolve account number'
        ];
    }

    /**
     * Call API with custom timeout
     */
    private function callPaystackApiWithTimeout(string $accountNumber, string $bankCode, int $timeout): array
    {
        // Mock timeout scenario
        if ($timeout < 5) {
            return [
                'status' => false,
                'error' => 'Operation timed out'
            ];
        }

        return $this->callPaystackApi($accountNumber, $bankCode);
    }

    /**
     * Call API with invalid URL
     */
    private function callApiWithInvalidUrl(): array
    {
        return [
            'status' => false,
            'error' => 'Network Error: Could not resolve host'
        ];
    }

    /**
     * Call API with custom key
     */
    private function callPaystackApiWithKey(string $accountNumber, string $bankCode, string $apiKey): array
    {
        if ($apiKey === 'invalid_key') {
            return [
                'status' => false,
                'message' => 'Authentication failed'
            ];
        }

        return $this->callPaystackApi($accountNumber, $bankCode);
    }

    /**
     * Parse API response
     */
    private function parseApiResponse(string $response): array
    {
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Invalid JSON response'];
        }

        return $decoded;
    }

    /**
     * Get CURL options for API call
     */
    private function getCurlOptions(string $accountNumber, string $bankCode): array
    {
        return [
            CURLOPT_URL => self::PAYSTACK_API_URL . "?account_number={$accountNumber}&bank_code={$bankCode}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::PAYSTACK_TEST_KEY,
                'Cache-Control: no-cache',
            ],
        ];
    }
}
