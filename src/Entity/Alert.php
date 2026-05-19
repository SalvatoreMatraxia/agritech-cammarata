<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private ?string $severity = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private bool $sentWhatsapp = false;

    #[ORM\Column]
    private bool $isExtremeEvent = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $economicImpact = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getSeverity(): ?string { return $this->severity; }
    public function setSeverity(string $severity): static { $this->severity = $severity; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function isIsRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function isSentWhatsapp(): bool { return $this->sentWhatsapp; }
    public function setSentWhatsapp(bool $sentWhatsapp): static { $this->sentWhatsapp = $sentWhatsapp; return $this; }

    public function isIsExtremeEvent(): bool { return $this->isExtremeEvent; }
    public function setIsExtremeEvent(bool $isExtremeEvent): static { $this->isExtremeEvent = $isExtremeEvent; return $this; }

    public function getEconomicImpact(): ?array { return $this->economicImpact; }
    public function setEconomicImpact(?array $economicImpact): static { $this->economicImpact = $economicImpact; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }
}