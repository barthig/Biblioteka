<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:180, unique:true)]
    private string $email;

    #[ORM\Column(type:'string', length:255)]
    private string $name;

    #[ORM\Column(type:'json')]
    private array $roles = [];

    #[ORM\Column(type:'string', length:255)]
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
