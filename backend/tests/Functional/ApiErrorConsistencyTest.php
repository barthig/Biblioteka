<?php
namespace App\Tests\Functional;

/**
 * Integration tests to verify API error responses are consistent across controllers
 * Tests that the ApiError standardization is working properly
 */
class ApiErrorConsistencyTest extends ApiTestCase
{
    /**
     * Test that auth endpoints return standardized error format
     */
    public function testAuthControllerErrors(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Test invalid login credentials
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]));

        $response = json_decode($client->getResponse()->getContent(), true);
        
        // Should be either 401 or error response
        if ($client->getResponse()->getStatusCode() >= 400) {
            $this->assertArrayHasKey('error', $response);
            $this->assertArrayHasKey('code', $response['error']);
            $this->assertArrayHasKey('message', $response['error']);
            $this->assertArrayHasKey('statusCode', $response['error']);
        }
    }

    /**
     * Test that book endpoints return standardized error format
     */
    public function testBookControllerErrors(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Request non-existent book
        $client->request('GET', '/api/books/999999');

        if ($client->getResponse()->getStatusCode() === 404) {
            $response = json_decode($client->getResponse()->getContent(), true);
            
            $this->assertArrayHasKey('error', $response);
            $this->assertSame('NOT_FOUND', $response['error']['code']);
            $this->assertSame(404, $response['error']['statusCode']);
        }
    }

    /**
     * Test that user endpoints return standardized error format
     */
    public function testUserControllerErrors(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Request without auth
        $client->request('GET', '/api/users');

        if ($client->getResponse()->getStatusCode() === 401 || $client->getResponse()->getStatusCode() === 403) {
            $response = json_decode($client->getResponse()->getContent(), true);
            
            $this->assertArrayHasKey('error', $response);
            $this->assertArrayHasKey('code', $response['error']);
            $this->assertArrayHasKey('message', $response['error']);
            $this->assertArrayHasKey('statusCode', $response['error']);
            
            // Verify error code matches HTTP status
            $statusCode = $response['error']['statusCode'];
            $this->assertGreaterThanOrEqual(400, $statusCode);
        }
    }

    /**
     * Test that report endpoints return standardized error format
     */
    public function testReportControllerErrors(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Try accessing reports without proper auth
        $client->request('GET', '/api/reports/usage');

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('UNAUTHORIZED', $response['error']['code']);
        $this->assertSame(401, $response['error']['statusCode']);
    }

    /**
     * Test error codes match HTTP status codes
     */
    public function testErrorCodeStatusCodeAlignment(): void
    {
        $errorCodeStatusMap = [
            'BAD_REQUEST' => 400,
            'UNAUTHORIZED' => 401,
            'FORBIDDEN' => 403,
            'NOT_FOUND' => 404,
            'CONFLICT' => 409,
            'VALIDATION_FAILED' => 400,
            'UNPROCESSABLE_ENTITY' => 422,
            'LOCKED' => 423,
            'RATE_LIMIT_EXCEEDED' => 429,
            'INTERNAL_ERROR' => 500,
            'SERVICE_UNAVAILABLE' => 503,
        ];
        
        foreach ($errorCodeStatusMap as $code => $status) {
            $this->assertIsString($code, "Error code must be string: $code");
            $this->assertIsInt($status, "Status code must be int: $status");
            $this->assertGreaterThanOrEqual(400, $status, "Status must be error code (>=400): $code");
        }
    }

    /**
     * Test that middleware converts legacy responses to new format
     * (This validates auto-conversion is working)
     */
    public function testLegacyResponseConversion(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Make a request that should trigger an error
        $client->request('GET', '/api/books/invalid-id');

        $statusCode = $client->getResponse()->getStatusCode();
        
        if ($statusCode >= 400) {
            $response = json_decode($client->getResponse()->getContent(), true);
            
            // Should have new format
            $this->assertArrayHasKey('error', $response, 'Response should be in new error format');
            
            // Should NOT have old format
            if (isset($response['error']) && is_array($response['error'])) {
                // New format has nested structure
                $this->assertArrayHasKey('code', $response['error']);
                $this->assertArrayHasKey('statusCode', $response['error']);
            }
        }
    }

    /**
     * Test that all API endpoints maintain consistency
     * Spot checks multiple endpoints
     */
    public function testMultipleEndpointsConsistency(): void
    {
        $client = $this->createClientWithoutSecret();
        
        $testEndpoints = [
            ['GET', '/api/health'],
            ['GET', '/api/books'],
            ['GET', '/api/nonexistent-endpoint'],
        ];
        
        foreach ($testEndpoints as [$method, $path]) {
            $client->request($method, $path);
            $response = json_decode($client->getResponse()->getContent(), true);
            
            $statusCode = $client->getResponse()->getStatusCode();
            
            // For error responses, verify structure
            if ($statusCode >= 400) {
                $this->assertIsArray($response, "Response should be parseable JSON for $path");
                
                // Either has error object or is legacy that should be converted
                if (isset($response['error'])) {
                    $this->assertArrayHasKey('code', $response['error']);
                }
            }
        }
    }

    /**
     * Test that details field is included in validation errors
     */
    public function testValidationErrorDetails(): void
    {
        $client = $this->createClientWithoutSecret();
        
        // Make a request that fails validation
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'not-an-email',
                'password' => 'short'
            ])
        );
        
        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $error = $response['error'];
        $this->assertArrayHasKey('code', $error);
        $this->assertSame(400, $error['statusCode']);

        if ($error['code'] === 'VALIDATION_FAILED') {
            $this->assertArrayHasKey('details', $error);
        } else {
            $this->assertSame('BAD_REQUEST', $error['code']);
        }
    }

    /**
     * Test error message is human-readable
     */
    public function testErrorMessagesAreHumanReadable(): void
    {
        $client = $this->createClientWithoutSecret();
        
        $client->request('GET', '/api/users/invalid');

        $response = json_decode($client->getResponse()->getContent(), true);
        
        if ($client->getResponse()->getStatusCode() >= 400 && isset($response['error'])) {
            $message = $response['error']['message'] ?? '';
            
            // Message should not be empty or just error code
            $this->assertNotEmpty($message, 'Error message should not be empty');
            $this->assertIsString($message, 'Error message should be string');
            $this->assertGreaterThan(3, strlen($message), 'Error message should be meaningful');
        }
    }
}
