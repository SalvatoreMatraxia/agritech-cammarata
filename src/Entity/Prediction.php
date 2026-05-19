<?php

namespace App\Entity;

use App\Repository\PredictionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PredictionRepository::class)]
class Prediction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::JSON)]
    private array $result = [];

    #[ORM\Column]
    private ?float $confidence = null;

    #[ORM\Column(length: 50)]
    private ?string $method = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $explanationText = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $scenarios = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $targetDate = null;

    #[ORM\ManyToOne(inversedBy: 'predictions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm = null;

    #[ORM\OneToMany(targetEntity: PredictionAccuracy::class, mappedBy: 'prediction', cascade: ['persist', 'remove'])]
    private Collection $predictionAccuracies;

    public function __construct()
    {
        $this->predictionAccuracies = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getResult(): array { return $this->result; }
    public function setResult(array $result): static { $this->result = $result; return $this; }

    public function getConfidence(): ?float { return $this->confidence; }
    public function setConfidence(float $confidence): static { $this->confidence = $confidence; return $this; }

    public function getMethod(): ?string { return $this->method; }
    public function setMethod(string $method): static { $this->method = $method; return $this; }

    public function getExplanationText(): ?string { return $this->explanationText; }
    public function setExplanationText(?string $explanationText): static { $this->explanationText = $explanationText; return $this; }

    public function getScenarios(): ?array { return $this->scenarios; }
    public function setScenarios(?array $scenarios): static { $this->scenarios = $scenarios; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getTargetDate(): ?\DateTimeImmutable { return $this->targetDate; }
    public function setTargetDate(\DateTimeImmutable $targetDate): static { $this->targetDate = $targetDate; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }

    /** @return Collection<int, PredictionAccuracy> */
    public function getPredictionAccuracies(): Collection { return $this->predictionAccuracies; }
    public function addPredictionAccuracy(PredictionAccuracy $accuracy): static
    {
        if (!$this->predictionAccuracies->contains($accuracy)) {
            $this->predictionAccuracies->add($accuracy);
            $accuracy->setPrediction($this);
        }
        return $this;
    }
    public function removePredictionAccuracy(PredictionAccuracy $accuracy): static
    {
        if ($this->predictionAccuracies->removeElement($accuracy) && $accuracy->getPrediction() === $this) {
            $accuracy->setPrediction(null);
        }
        return $this;
    }
}