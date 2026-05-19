<?php

namespace App\Entity;

use App\Repository\FarmSeasonRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FarmSeasonRecordRepository::class)]
class FarmSeasonRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(length: 20)]
    private ?string $season = null;

    #[ORM\Column(nullable: true)]
    private ?float $actualYieldKg = null;

    #[ORM\Column(nullable: true)]
    private ?float $actualOilLiters = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $oilQuality = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $pestEvents = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $diseaseEvents = null;

    #[ORM\Column]
    private int $treatmentsCount = 0;

    #[ORM\Column(nullable: true)]
    private ?float $irrigationTotalMc = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $weatherSummary = null;

    #[ORM\Column(nullable: true)]
    private ?float $predictedYieldKg = null;

    #[ORM\Column(nullable: true)]
    private ?float $predictionErrorPct = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'farmSeasonRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm = null;

    public function getId(): ?int { return $this->id; }

    public function getYear(): ?int { return $this->year; }
    public function setYear(int $year): static { $this->year = $year; return $this; }

    public function getSeason(): ?string { return $this->season; }
    public function setSeason(string $season): static { $this->season = $season; return $this; }

    public function getActualYieldKg(): ?float { return $this->actualYieldKg; }
    public function setActualYieldKg(?float $actualYieldKg): static { $this->actualYieldKg = $actualYieldKg; return $this; }

    public function getActualOilLiters(): ?float { return $this->actualOilLiters; }
    public function setActualOilLiters(?float $actualOilLiters): static { $this->actualOilLiters = $actualOilLiters; return $this; }

    public function getOilQuality(): ?string { return $this->oilQuality; }
    public function setOilQuality(?string $oilQuality): static { $this->oilQuality = $oilQuality; return $this; }

    public function getPestEvents(): ?array { return $this->pestEvents; }
    public function setPestEvents(?array $pestEvents): static { $this->pestEvents = $pestEvents; return $this; }

    public function getDiseaseEvents(): ?array { return $this->diseaseEvents; }
    public function setDiseaseEvents(?array $diseaseEvents): static { $this->diseaseEvents = $diseaseEvents; return $this; }

    public function getTreatmentsCount(): int { return $this->treatmentsCount; }
    public function setTreatmentsCount(int $treatmentsCount): static { $this->treatmentsCount = $treatmentsCount; return $this; }

    public function getIrrigationTotalMc(): ?float { return $this->irrigationTotalMc; }
    public function setIrrigationTotalMc(?float $irrigationTotalMc): static { $this->irrigationTotalMc = $irrigationTotalMc; return $this; }

    public function getWeatherSummary(): ?array { return $this->weatherSummary; }
    public function setWeatherSummary(?array $weatherSummary): static { $this->weatherSummary = $weatherSummary; return $this; }

    public function getPredictedYieldKg(): ?float { return $this->predictedYieldKg; }
    public function setPredictedYieldKg(?float $predictedYieldKg): static { $this->predictedYieldKg = $predictedYieldKg; return $this; }

    public function getPredictionErrorPct(): ?float { return $this->predictionErrorPct; }
    public function setPredictionErrorPct(?float $predictionErrorPct): static { $this->predictionErrorPct = $predictionErrorPct; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }
}