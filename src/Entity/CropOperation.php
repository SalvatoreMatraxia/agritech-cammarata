<?php

namespace App\Entity;

use App\Repository\CropOperationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CropOperationRepository::class)]
class CropOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?float $durationHours = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $operator = null;

    #[ORM\Column(nullable: true)]
    private ?float $costEur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'cropOperations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcel $parcel = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDurationHours(): ?float { return $this->durationHours; }
    public function setDurationHours(?float $durationHours): static { $this->durationHours = $durationHours; return $this; }

    public function getOperator(): ?string { return $this->operator; }
    public function setOperator(?string $operator): static { $this->operator = $operator; return $this; }

    public function getCostEur(): ?float { return $this->costEur; }
    public function setCostEur(?float $costEur): static { $this->costEur = $costEur; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getParcel(): ?Parcel { return $this->parcel; }
    public function setParcel(?Parcel $parcel): static { $this->parcel = $parcel; return $this; }
}