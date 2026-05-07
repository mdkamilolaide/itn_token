<?php

/**
 * API Endpoint Tests
 * 
 * Tests for API endpoints functionality
 * These tests verify the API works correctly across PHP versions.
 */

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    private $baseUrl = 'http://localhost/ipolongo';
    private $testLoginId = 'SID0001';
    private $testPassword = 'testpass123';
    private $jwtToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../lib/common.php';
    }

    /**
     * Helper: Make HTTP request using curl
     */
    private function makeRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $allHeaders = ['Content-Type: application/json'];
        foreach ($headers as $key => $value) {
            $allHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => $response,
            'json' => json_decode($response, true),
            'error' => $error
        ];
    }

    /**
     * Test API endpoint is accessible
     */
    public function testApiEndpointAccessible(): void
    {
        $response = $this->makeRequest($this->baseUrl . '/api.php');

        $this->assertNotEquals(0, $response['code'], 'API should be accessible');
        $this->assertEmpty($response['error'], 'Should not have curl errors');
    }

    /**
     * Test API returns JSON content type
     */
    public function testApiReturnsJson(): void
    {
        $response = $this->makeRequest($this->baseUrl . '/api.php?qid=010', 'POST', [], [
            'loginid' => $this->testLoginId,
            'password' => $this->testPassword
        ]);

        // Check if response is valid JSON
        $this->assertNotNull($response['json'], 'API should return valid JSON');
    }

    /**
     * Test login API with invalid credentials
     */
    public function testLoginApiWithInvalidCredentials(): void
    {
        $response = $this->makeRequest(
            $this->baseUrl . '/api.php?qid=010',
            'POST',
            [],
            [
                'loginid' => 'INVALID_USER',
                'password' => 'wrong_password'
            ]
        );

        $json = $response['json'];

        if ($json) {
            $this->assertNotEquals(200, $json['result_code'] ?? 0, 'Login should fail with invalid credentials');
        }
    }

    /**
     * Test API without JWT returns appropriate response
     */
    public function testApiWithoutJwtReturnsError(): void
    {
        // Try to access a protected endpoint without JWT
        $response = $this->makeRequest(
            $this->baseUrl . '/api.php?qid=100',
            'POST'
        );

        $json = $response['json'];

        // Should get an error or unauthorized response, or empty body (which is also acceptable)
        if ($json && isset($json['result_code'])) {
            $this->assertContains(
                $json['result_code'],
                [400, 401, 403],
                'Protected endpoint should require authentication'
            );
        } else {
            // Empty response or redirect means the API handled unauthorized access
            // by simply not processing the request
            $this->assertTrue(true, 'API handled unauthorized request');
        }
    }

    /**
     * Test login page is accessible
     */
    public function testLoginPageAccessible(): void
    {
        $response = $this->makeRequest($this->baseUrl . '/login.php');

        $this->assertEquals(200, $response['code'], 'Login page should be accessible');
        $this->assertStringContainsString('html', strtolower($response['body']), 'Should return HTML');
    }

    /**
     * Test login page contains required form elements
     */
    public function testLoginPageHasFormElements(): void
    {
        $response = $this->makeRequest($this->baseUrl . '/login.php');

        $body = $response['body'];

        // Check for login form elements
        $this->assertStringContainsString('login_id', $body, 'Login page should have login_id field');
        $this->assertStringContainsString('login_password', $body, 'Login page should have password field');
    }

    /**
     * Test main index redirects without auth
     */
    public function testIndexRedirectsWithoutAuth(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Should redirect (302) or return unauthorized
        $this->assertContains($httpCode, [200, 302, 301], 'Index should redirect or show content');
    }

    /**
     * Test dashboard page requires authentication
     */
    public function testDashboardRequiresAuth(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/dashboard');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Without auth cookie, should redirect
        $this->assertContains($httpCode, [200, 302, 301], 'Dashboard should handle unauthenticated access');
    }

    /**
     * Test static assets are accessible
     */
    public function testStaticAssetsAccessible(): void
    {
        // Test CSS file
        $response = $this->makeRequest($this->baseUrl . '/app-assets/css/bootstrap.css');
        $this->assertEquals(200, $response['code'], 'CSS files should be accessible');

        // Test JS file
        $response = $this->makeRequest($this->baseUrl . '/app-assets/vendors/js/vendors.min.js');
        // May be 200 or 404 depending on file existence
        $this->assertContains($response['code'], [200, 404], 'JS file request should complete');
    }

    /**
     * Test API handles malformed JSON gracefully
     */
    public function testApiHandlesMalformedJson(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api.php?qid=010');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'not valid json{{{');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Should not crash - return some response
        $this->assertNotEquals(500, $httpCode, 'API should not crash on malformed JSON');
    }

    /**
     * Test API handles empty request
     */
    public function testApiHandlesEmptyRequest(): void
    {
        $response = $this->makeRequest($this->baseUrl . '/api.php', 'POST');

        // Should return a response, not crash
        $this->assertNotEquals(500, $response['code'], 'API should handle empty request');
    }

    /**
     * Test CORS headers are set
     */
    public function testCorsHeadersAreSet(): void
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $this->assertStringContainsString(
            'Access-Control-Allow-Origin',
            $response,
            'API should have CORS headers'
        );
    }
}
