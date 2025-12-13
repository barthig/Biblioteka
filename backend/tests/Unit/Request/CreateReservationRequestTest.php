<?php

namespace App\Tests\Unit\Request;

use App\Request\CreateReservationRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CreateReservationRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

    public function testDaysFieldExists(): void
    {
        $request = new CreateReservationRequest();
        $this->assertObjectHasProperty('days', $request);
    }

    public function testDefaultDaysValue(): void
    {
        $request = new CreateReservationRequest();
        $this->assertEquals(3, $request->days);
    }

    public function testDaysValidationAcceptsValidRange(): void
    {
        $request = new CreateReservationRequest();
        $request->bookId = 1;
        $request->days = 7;

        $violations = $this->validator->validate($request);
        
        foreach ($violations as $violation) {
            $this->assertStringNotContainsString('days', $violation->getPropertyPath());
        }
    }

    public function testDaysValidationRejectsTooLow(): void
    {
        $request = new CreateReservationRequest();
        $request->bookId = 1;
        $request->days = 0;

        $violations = $this->validator->validate($request);
        
        $this->assertGreaterThan(0, count($violations));
        
        $hasRangeError = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'days') {
                $hasRangeError = true;
            }
        }
        $this->assertTrue($hasRangeError);
    }

    public function testDaysValidationRejectsTooHigh(): void
    {
        $request = new CreateReservationRequest();
        $request->bookId = 1;
        $request->days = 15;

        $violations = $this->validator->validate($request);
        
        $this->assertGreaterThan(0, count($violations));
        
        $hasRangeError = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), 'od 1 do 14')) {
                $hasRangeError = true;
            }
        }
        $this->assertTrue($hasRangeError);
    }
}
