<?php
namespace App\Tests\Unit;

use App\Entity\AcquisitionBudget;
use PHPUnit\Framework\TestCase;

class AcquisitionBudgetTest extends TestCase
{
    public function testRegisterExpenseAndAdjustments(): void
    {
        $budget = (new AcquisitionBudget())
            ->setName('Unit Budget')
            ->setFiscalYear('2025')
            ->setCurrency('PLN')
            ->setAllocatedAmount('1000.00');

        $budget->registerExpense('100.50');
        $this->assertSame('100.50', $budget->getSpentAmount());
        $this->assertSame('899.50', $budget->remainingAmount());

        $budget->adjustSpentBy('-50.25');
        $this->assertSame('50.25', $budget->getSpentAmount());
        $this->assertSame('949.75', $budget->remainingAmount());

        $budget->adjustSpentBy('-999.99');
        $this->assertSame('0.00', $budget->getSpentAmount());
        $this->assertSame('1000.00', $budget->remainingAmount());
    }
}
