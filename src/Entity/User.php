<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $firstName;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $lastName;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $keycloakId;

    /**
     * @Gedmo\Slug(fields={"firstName","lastName"})
     * @ORM\Column(length=255, unique=true)
     */
    protected string $slug;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"registration"})
     * @Assert\Length(min=7, groups={"registration"})
     */
    private ?string $password = null;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private string $email;

    public function setFirstName(string $firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setKeycloakId(string $keycloakId): User
    {
        $this->keycloakId = $keycloakId;

        return $this;
    }

    public function getKeycloakId(): string
    {
        return $this->keycloakId;
    }

    public function setSlug(string $slug): User
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
