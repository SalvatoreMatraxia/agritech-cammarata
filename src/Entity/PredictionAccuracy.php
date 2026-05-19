<?php

namespace App\Entity;

use App\Repository\PredictionAccuracyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PredictionAccuracyRepository::class)]
class PredictionAccuracy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $actualValue = null;

    #[ORM\Column]
    private ?float $errorAbsolute = null;

    #[ORM\Column]
    private ?float $errorPct = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $validatedBy = null;

    #[ORM\ManyToOne(inversedBy: 'predictionAccuracies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Prediction $prediction = null;

    public function __construct()
    {
        $this->validatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getActualValue(): ?float { return $this->actualValue; }
    public function setActualValue(float $actualValue): static { $this->actualValue = $actualValue; return $this; }

    public function getErrorAbsolute(): ?float { return $this->errorAbsolute; }
    public function setErrorAbsolute(float $errorAbsolute): static { $this->errorAbsolute = $errorAbsolute; return $this; }

    public function getErrorPct(): ?float { return $this->errorPct; }
    public function setErrorPct(float $errorPct): static { $this->errorPct = $errorPct; return $this; }

    public function getValidatedAt(): ?\DateTimeImmutable { return $this->validatedAt; }
    public function setValidatedAt(\DateTimeImmutable $validatedAt): static { $this->validatedAt = $validatedAt; return $this; }

    public function getValidatedBy(): ?string { return $this->validatedBy; }
    public function setValidatedBy(?string $validatedBy): static { $this->validatedBy = $validatedBy; return $this; }

    public function getPrediction(): ?Prediction { return $this->prediction; }
    public function setPrediction(?Prediction $prediction): static { $this->prediction = $prediction; return $this; }
}