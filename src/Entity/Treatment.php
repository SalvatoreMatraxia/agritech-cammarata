<?php

namespace App\Entity;

use App\Repository\TreatmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TreatmentRepository::class)]
class Treatment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(length: 255)]
    private ?string $activeSubstance = null;

    #[ORM\Column]
    private ?float $dosePerHa = null;

    #[ORM\Column(length: 20)]
    private ?string $doseUnit = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalQuantity = null;

    #[ORM\Column(length: 100)]
    private ?string $targetPest = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $applicationMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $weatherConditions = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $operator = null;

    #[ORM\Column]
    private bool $isOrganic = true;

    #[ORM\Column(length: 20)]
    private string $registeredVia = 'web';

    #[ORM\Column(nullable: true)]
    private ?float $costEur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'treatments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcel $parcel = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }

    public function getProductName(): ?string { return $this->productName; }
    public function setProductName(string $productName): static { $this->productName = $productName; return $this; }

    public function getActiveSubstance(): ?string { return $this->activeSubstance; }
    public function setActiveSubstance(string $activeSubstance): static { $this->activeSubstance = $activeSubstance; return $this; }

    public function getDosePerHa(): ?float { return $this->dosePerHa; }
    public function setDosePerHa(float $dosePerHa): static { $this->dosePerHa = $dosePerHa; return $this; }

    public function getDoseUnit(): ?string { return $this->doseUnit; }
    public function setDoseUnit(string $doseUnit): static { $this->doseUnit = $doseUnit; return $this; }

    public function getTotalQuantity(): ?float { return $this->totalQuantity; }
    public function setTotalQuantity(?float $totalQuantity): static { $this->totalQuantity = $totalQuantity; return $this; }

    public function getTargetPest(): ?string { return $this->targetPest; }
    public function setTargetPest(string $targetPest): static { $this->targetPest = $targetPest; return $this; }

    public function getApplicationMethod(): ?string { return $this->applicationMethod; }
    public function setApplicationMethod(?string $applicationMethod): static { $this->applicationMethod = $applicationMethod; return $this; }

    public function getWeatherConditions(): ?string { return $this->weatherConditions; }
    public function setWeatherConditions(?string $weatherConditions): static { $this->weatherConditions = $weatherConditions; return $this; }

    public function getOperator(): ?string { return $this->operator; }
    public function setOperator(?string $operator): static { $this->operator = $operator; return $this; }

    public function isIsOrganic(): bool { return $this->isOrganic; }
    public function setIsOrganic(bool $isOrganic): static { $this->isOrganic = $isOrganic; return $this; }

    public function getRegisteredVia(): string { return $this->registeredVia; }
    public function setRegisteredVia(string $registeredVia): static { $this->registeredVia = $registeredVia; return $this; }

    public function getCostEur(): ?float { return $this->costEur; }
    public function setCostEur(?float $costEur): static { $this->costEur = $costEur; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getParcel(): ?Parcel { return $this->parcel; }
    public function setParcel(?Parcel $parcel): static { $this->parcel = $parcel; return $this; }
}