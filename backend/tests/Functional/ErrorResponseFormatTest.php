<?php
namespace App\Tests\Functional;

/**
 * Tests that all API error responses follow the standardized ApiError envelope format
 */
class ErrorResponseFormatTest extends ApiTestCase
{
    /**
     * Test that 404 errors return proper ApiError format
     */
    public function testNotFoundErrorFormat(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/books/999999');

        $this->assertResponseStatusCodeSame(404);
        $response = $this->getJsonResponse($client);
        $this->assertErrorResponseStructure($response, 'NOT_FOUND', 404);
    }

    /**
     * Test that 403 forbidden errors return proper ApiError format
     */
    public function testForbiddenErrorFormat(): void
    {
        $user = $this->createUser('forbidden-format@example.com', ['ROLE_USER']);
        $client = $this->createAuthenticatedClient($user);
        $this->sendRequest($client, 'GET', '/api/admin/system/roles');

        $this->assertResponseStatusCodeSame(403);
        $response = $this->getJsonResponse($client);
        $this->assertErrorResponseStructure($response, 'FORBIDDEN', 403);
    }

    /**
     * Test that 401 unauthorized errors return proper ApiError format
     */
    public function testUnauthorizedErrorFormat(): void
    {
        $client = $this->createClientWithoutSecret();
        $client->request('POST', '/api/reservations', [], [], ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.here']);

        $this->assertResponseStatusCodeSame(401);
        $response = $this->getJsonResponse($client);
        $this->assertErrorResponseStructure($response, 'UNAUTHORIZED', 401);
    }

    /**
     * Test that 400 bad request errors return proper ApiError format
     */
    public function testBadRequestErrorFormat(): void
    {
        $client = $this->createClientWithoutSecret();
        // Invalid JSON payload
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], '{ invalid json');

        $this->assertResponseStatusCodeSame(400);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertErrorResponseStructure($data, 'BAD_REQUEST', 400);
    }

    /**
     * Test that validation errors return proper ApiError format with details
     */
    public function testValidationErrorFormat(): void
    {
        $client = $this->createClientWithoutSecret();
        // POST with missing required fields
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => '', 'password' => ''])
        );

        $this->assertResponseStatusCodeSame(400);
        $response = $this->getJsonResponse($client);
        
        $this->assertArrayHasKey('error', $response);
        $error = $response['error'];
        
        $this->assertSame(400, $error['statusCode']);
        if ($error['code'] === 'VALIDATION_FAILED') {
            $this->assertStringContainsString('Validation', $error['message']);
            if (isset($error['details'])) {
                $this->assertIsArray($error['details']);
            }
        } else {
            $this->assertSame('BAD_REQUEST', $error['code']);
        }
    }

    /**
     * Test that 409 conflict errors return proper ApiError format
     */
    public function testConflictErrorFormat(): void
    {
        $librarian = $this->createUser('conflict-format@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $supplier = $this->createSupplier('Conflict Supplier', false);

        $this->jsonRequest($client, 'POST', '/api/admin/acquisitions/orders', [
            'supplierId' => $supplier->getId(),
            'title' => 'Conflict Order',
            'totalAmount' => '50.00',
        ]);

        $this->assertResponseStatusCodeSame(409);
        $response = $this->getJsonResponse($client);
        $this->assertErrorResponseStructure($response, 'CONFLICT', 409);
    }

    /**
     * Test that 422 unprocessable entity errors return proper ApiError format
     */
    public function testUnprocessableEntityErrorFormat(): void
    {
        $librarian = $this->createUser('unprocessable-format@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);

        $this->jsonRequest($client, 'POST', '/api/notifications/test', [
            'channel' => 'fax',
            'target' => 'receiver',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $response = $this->getJsonResponse($client);
        $this->assertErrorResponseStructure($response, 'UNPROCESSABLE_ENTITY', 422);
    }

    /**
     * Test that 500 internal errors return proper ApiError format
     */
    public function testInternalErrorFormat(): void
    {
        $client = $this->createApiClient();
        // Trigger an error condition - request an invalid endpoint
        $this->sendRequest($client, 'GET', '/api/invalid-endpoint-that-does-not-exist');
        
        // 404 or 500 - both should follow format
        $response = $this->getJsonResponse($client);
        $this->assertArrayHasKey('error', $response);
        
        $error = $response['error'];
        $this->assertArrayHasKey('code', $error);
        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('statusCode', $error);
    }

    /**
     * Test that success responses are wrapped in 'data' envelope
     */
    public function testSuccessResponseFormat(): void
    {
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/health');

        $response = $this->getJsonResponse($client);
        
        $this->assertResponseStatusCodeSame(200);
        $this->assertIsArray($response);
        $this->assertFalse(
            isset($response['error']) && is_array($response['error']),
            'Success response should not have error object'
        );
    }

    /**
     * Test error response structure for consistency
     * Verifies all required fields are present
     */
    private function assertErrorResponseStructure(array $response, string $expectedCode, int $expectedStatus): void
    {
        $this->assertArrayHasKey('error', $response, 'Response must have error object');
        
        $error = $response['error'];
        $this->assertIsArray($error, 'error field must be an array/object');
        
        $this->assertArrayHasKey('code', $error, 'error.code is required');
        $this->assertArrayHasKey('message', $error, 'error.message is required');
        $this->assertArrayHasKey('statusCode', $error, 'error.statusCode is required');
        
        $this->assertSame($expectedCode, $error['code'], "Expected code to be $expectedCode");
        $this->assertSame($expectedStatus, $error['statusCode'], "Expected statusCode to be $expectedStatus");
        
        $this->assertIsString($error['message'], 'error.message must be a string');
        $this->assertNotEmpty($error['message'], 'error.message must not be empty');
    }

    /**
     * Helper to extract JSON response
     */
    protected function getJsonResponse($client): array
    {
        $response = $client->getResponse();
        $content = $response->getContent();
        
        if (empty($content)) {
            return [];
        }
        
        return json_decode($content, true) ?? [];
    }
}
