<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['loan:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['loan:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['loan:read'])]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $password;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $e): self { $this->email = $e; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): self { $this->name = $n; return $this; }
    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $r): self { $this->roles = $r; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hashed): self { $this->password = $hashed; return $this; }
}
