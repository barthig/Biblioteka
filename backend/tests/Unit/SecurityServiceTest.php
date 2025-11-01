<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Service\SecurityService;
use Symfony\Component\HttpFoundation\Request;

class SecurityServiceTest extends TestCase
{
    public function testGetJwtPayloadAndHasRole()
    {
        $req = new Request();
        $payload = ['sub' => 2, 'roles' => ['ROLE_USER']];
        $req->attributes->set('jwt_payload', $payload);

        $s = new SecurityService();
        $this->assertSame($payload, $s->getJwtPayload($req));
        $this->assertTrue($s->hasRole($req, 'ROLE_USER'));
        $this->assertFalse($s->hasRole($req, 'ROLE_LIBRARIAN'));
    }
}
