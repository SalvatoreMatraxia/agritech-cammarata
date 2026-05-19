<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column]
    private int $messageCount = 0;

    #[ORM\ManyToOne(inversedBy: 'conversations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: ConversationMessage::class, mappedBy: 'conversation', cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->startedAt = new \DateTimeImmutable();
        $this->lastMessageAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(\DateTimeImmutable $startedAt): static { $this->startedAt = $startedAt; return $this; }

    public function getLastMessageAt(): ?\DateTimeImmutable { return $this->lastMessageAt; }
    public function setLastMessageAt(\DateTimeImmutable $lastMessageAt): static { $this->lastMessageAt = $lastMessageAt; return $this; }

    public function getMessageCount(): int { return $this->messageCount; }
    public function setMessageCount(int $messageCount): static { $this->messageCount = $messageCount; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    /** @return Collection<int, ConversationMessage> */
    public function getMessages(): Collection { return $this->messages; }
    public function addMessage(ConversationMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }
        return $this;
    }
    public function removeMessage(ConversationMessage $message): static
    {
        if ($this->messages->removeElement($message) && $message->getConversation() === $this) {
            $message->setConversation(null);
        }
        return $this;
    }
}