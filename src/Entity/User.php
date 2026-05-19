<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /** @var list<string> The user roles */
    #[ORM\Column]
    private array $roles = [];

    /** @var string The hashed password */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private bool $whatsappOptIn = false;

    #[ORM\Column(length: 5)]
    private string $language = 'it';

    #[ORM\OneToMany(targetEntity: Farm::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $farms;

    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $conversations;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->farms = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    /** @see UserInterface */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    /** @see PasswordAuthenticatedUserInterface */
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void {}

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function isWhatsappOptIn(): bool { return $this->whatsappOptIn; }
    public function setWhatsappOptIn(bool $whatsappOptIn): static { $this->whatsappOptIn = $whatsappOptIn; return $this; }

    public function getLanguage(): string { return $this->language; }
    public function setLanguage(string $language): static { $this->language = $language; return $this; }

    /** @return Collection<int, Farm> */
    public function getFarms(): Collection { return $this->farms; }
    public function addFarm(Farm $farm): static
    {
        if (!$this->farms->contains($farm)) {
            $this->farms->add($farm);
            $farm->setUser($this);
        }
        return $this;
    }
    public function removeFarm(Farm $farm): static
    {
        if ($this->farms->removeElement($farm) && $farm->getUser() === $this) {
            $farm->setUser(null);
        }
        return $this;
    }

    /** @return Collection<int, Conversation> */
    public function getConversations(): Collection { return $this->conversations; }
    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setUser($this);
        }
        return $this;
    }
    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation) && $conversation->getUser() === $this) {
            $conversation->setUser(null);
        }
        return $this;
    }

    /** @return Collection<int, Subscription> */
    public function getSubscriptions(): Collection { return $this->subscriptions; }
    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }
        return $this;
    }
    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription) && $subscription->getUser() === $this) {
            $subscription->setUser(null);
        }
        return $this;
    }
}