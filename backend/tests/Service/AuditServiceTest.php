<?php
namespace App\Tests\Service;

use App\Entity\AuditLog;
use App\Service\System\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditServiceTest extends TestCase
{
    public function testLogCreatePersistsAuditLog(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $stack = new RequestStack();
        $stack->push($request);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->callback(
            function ($entity) {
                if (!$entity instanceof AuditLog) {
                    return false;
                }
                return $entity->getEntityType() === 'Book'
                    && $entity->getAction() === 'CREATE'
                    && $entity->getIpAddress() === '127.0.0.1';
            }
        ));
        $entityManager->expects($this->once())->method('flush');

        $service = new AuditService($entityManager, $stack);
        $service->logCreate('Book', 1, null, ['title' => 'Test']);
    }
}
