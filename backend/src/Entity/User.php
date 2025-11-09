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
    #[Groups(['loan:read', 'reservation:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['loan:read', 'reservation:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['loan:read', 'reservation:read'])]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $password;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Groups(['loan:read'])]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $addressLine = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 12, nullable: true)]
    private ?string $postalCode = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $e): self { $this->email = $e; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): self { $this->name = $n; return $this; }
    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $r): self { $this->roles = $r; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hashed): self { $this->password = $hashed; return $this; }

    public function getPhoneNumber(): ?string { return $this->phoneNumber; }
    public function setPhoneNumber(?string $phone): self { $this->phoneNumber = $phone; return $this; }
    public function getAddressLine(): ?string { return $this->addressLine; }
    public function setAddressLine(?string $address): self { $this->addressLine = $address; return $this; }
    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): self { $this->city = $city; return $this; }
    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self { $this->postalCode = $postalCode; return $this; }
}
