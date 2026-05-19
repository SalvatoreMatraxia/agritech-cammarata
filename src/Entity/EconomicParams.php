<?php

namespace App\Entity;

use App\Repository\EconomicParamsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EconomicParamsRepository::class)]
class EconomicParams
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private float $oilPriceEur = 14.0;

    #[ORM\Column]
    private float $waterCostEur = 2.0;

    #[ORM\Column]
    private float $laborCostEur = 12.0;

    #[ORM\Column]
    private float $defaultYieldKgHa = 3000.0;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'economicParams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getOilPriceEur(): float { return $this->oilPriceEur; }
    public function setOilPriceEur(float $oilPriceEur): static { $this->oilPriceEur = $oilPriceEur; return $this; }

    public function getWaterCostEur(): float { return $this->waterCostEur; }
    public function setWaterCostEur(float $waterCostEur): static { $this->waterCostEur = $waterCostEur; return $this; }

    public function getLaborCostEur(): float { return $this->laborCostEur; }
    public function setLaborCostEur(float $laborCostEur): static { $this->laborCostEur = $laborCostEur; return $this; }

    public function getDefaultYieldKgHa(): float { return $this->defaultYieldKgHa; }
    public function setDefaultYieldKgHa(float $defaultYieldKgHa): static { $this->defaultYieldKgHa = $defaultYieldKgHa; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }
}