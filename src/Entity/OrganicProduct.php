<?php

namespace App\Entity;

use App\Repository\OrganicProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganicProductRepository::class)]
class OrganicProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $commercialName = null;

    #[ORM\Column(length: 255)]
    private ?string $activeSubstance = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $registrationNumber = null;

    #[ORM\Column]
    private bool $isAllowedOrganic = true;

    #[ORM\Column(nullable: true)]
    private ?float $maxDosePerHa = null;

    #[ORM\Column(nullable: true)]
    private ?int $waitingDays = null;

    #[ORM\Column(nullable: true)]
    private ?float $costPerHa = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int { return $this->id; }

    public function getCommercialName(): ?string { return $this->commercialName; }
    public function setCommercialName(string $commercialName): static { $this->commercialName = $commercialName; return $this; }

    public function getActiveSubstance(): ?string { return $this->activeSubstance; }
    public function setActiveSubstance(string $activeSubstance): static { $this->activeSubstance = $activeSubstance; return $this; }

    public function getRegistrationNumber(): ?string { return $this->registrationNumber; }
    public function setRegistrationNumber(?string $registrationNumber): static { $this->registrationNumber = $registrationNumber; return $this; }

    public function isIsAllowedOrganic(): bool { return $this->isAllowedOrganic; }
    public function setIsAllowedOrganic(bool $isAllowedOrganic): static { $this->isAllowedOrganic = $isAllowedOrganic; return $this; }

    public function getMaxDosePerHa(): ?float { return $this->maxDosePerHa; }
    public function setMaxDosePerHa(?float $maxDosePerHa): static { $this->maxDosePerHa = $maxDosePerHa; return $this; }

    public function getWaitingDays(): ?int { return $this->waitingDays; }
    public function setWaitingDays(?int $waitingDays): static { $this->waitingDays = $waitingDays; return $this; }

    public function getCostPerHa(): ?float { return $this->costPerHa; }
    public function setCostPerHa(?float $costPerHa): static { $this->costPerHa = $costPerHa; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
}