<?php

namespace App\Entity;

use App\Repository\SensorDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SensorDataRepository::class)]
class SensorData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $sensorType = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(length: 20)]
    private ?string $unit = null;

    #[ORM\Column(length: 100)]
    private ?string $sensorId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $recordedAt = null;

    #[ORM\ManyToOne(inversedBy: 'sensorData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parcel $parcel = null;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSensorType(): ?string { return $this->sensorType; }
    public function setSensorType(string $sensorType): static { $this->sensorType = $sensorType; return $this; }

    public function getValue(): ?float { return $this->value; }
    public function setValue(float $value): static { $this->value = $value; return $this; }

    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(string $unit): static { $this->unit = $unit; return $this; }

    public function getSensorId(): ?string { return $this->sensorId; }
    public function setSensorId(string $sensorId): static { $this->sensorId = $sensorId; return $this; }

    public function getRecordedAt(): ?\DateTimeImmutable { return $this->recordedAt; }
    public function setRecordedAt(\DateTimeImmutable $recordedAt): static { $this->recordedAt = $recordedAt; return $this; }

    public function getParcel(): ?Parcel { return $this->parcel; }
    public function setParcel(?Parcel $parcel): static { $this->parcel = $parcel; return $this; }
}