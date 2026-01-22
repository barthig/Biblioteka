<?php
namespace App\Tests\Service;

use App\Service\RegistrationException;
use PHPUnit\Framework\TestCase;

class RegistrationExceptionTest extends TestCase
{
    public function testValidationFactorySetsMessageAndCode(): void
    {
        $exception = RegistrationException::validation('Invalid', 409);
        $this->assertSame('Invalid', $exception->getMessage());
        $this->assertSame(409, $exception->getCode());
    }
}
