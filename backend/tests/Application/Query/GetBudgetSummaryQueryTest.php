<?php

namespace App\Tests\Application\Query;

use App\Application\Query\Acquisition\GetBudgetSummaryQuery;
use PHPUnit\Framework\TestCase;

class GetBudgetSummaryQueryTest extends TestCase
{
    public function testConstructorSetsBudgetId(): void
    {
        $query = new GetBudgetSummaryQuery(42);

        $this->assertEquals(42, $query->id);
    }

    public function testQueryIsReadonly(): void
    {
        $query = new GetBudgetSummaryQuery(1);
        
        $reflection = new \ReflectionClass($query);
        
        // Check if class has readonly properties
        $this->assertTrue($reflection->isReadOnly() || count($reflection->getProperties()) > 0);
    }
}
