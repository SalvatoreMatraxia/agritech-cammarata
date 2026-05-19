<?php

namespace App\Entity;

use App\Repository\ParcelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParcelRepository::class)]
class Parcel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $surface = null;

    #[ORM\Column]
    private ?int $treeCount = null;

    #[ORM\Column(length: 100)]
    private ?string $variety = null;

    #[ORM\Column]
    private ?int $plantingYear = null;

    #[ORM\Column]
    private bool $isBiological = true;

    #[ORM\Column(nullable: true)]
    private ?int $altitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $slope = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $aspect = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $windExposure = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'parcels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm = null;

    #[ORM\OneToMany(targetEntity: SensorData::class, mappedBy: 'parcel', cascade: ['persist', 'remove'])]
    private Collection $sensorData;

    #[ORM\OneToMany(targetEntity: Treatment::class, mappedBy: 'parcel', cascade: ['persist', 'remove'])]
    private Collection $treatments;

    #[ORM\OneToMany(targetEntity: CropOperation::class, mappedBy: 'parcel', cascade: ['persist', 'remove'])]
    private Collection $cropOperations;

    public function __construct()
    {
        $this->sensorData = new ArrayCollection();
        $this->treatments = new ArrayCollection();
        $this->cropOperations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSurface(): ?float { return $this->surface; }
    public function setSurface(float $surface): static { $this->surface = $surface; return $this; }

    public function getTreeCount(): ?int { return $this->treeCount; }
    public function setTreeCount(int $treeCount): static { $this->treeCount = $treeCount; return $this; }

    public function getVariety(): ?string { return $this->variety; }
    public function setVariety(string $variety): static { $this->variety = $variety; return $this; }

    public function getPlantingYear(): ?int { return $this->plantingYear; }
    public function setPlantingYear(int $plantingYear): static { $this->plantingYear = $plantingYear; return $this; }

    public function isIsBiological(): bool { return $this->isBiological; }
    public function setIsBiological(bool $isBiological): static { $this->isBiological = $isBiological; return $this; }

    public function getAltitude(): ?int { return $this->altitude; }
    public function setAltitude(?int $altitude): static { $this->altitude = $altitude; return $this; }

    public function getSlope(): ?float { return $this->slope; }
    public function setSlope(?float $slope): static { $this->slope = $slope; return $this; }

    public function getAspect(): ?string { return $this->aspect; }
    public function setAspect(?string $aspect): static { $this->aspect = $aspect; return $this; }

    public function getWindExposure(): ?string { return $this->windExposure; }
    public function setWindExposure(?string $windExposure): static { $this->windExposure = $windExposure; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }

    /** @return Collection<int, SensorData> */
    public function getSensorData(): Collection { return $this->sensorData; }
    public function addSensorData(SensorData $sensorData): static
    {
        if (!$this->sensorData->contains($sensorData)) {
            $this->sensorData->add($sensorData);
            $sensorData->setParcel($this);
        }
        return $this;
    }
    public function removeSensorData(SensorData $sensorData): static
    {
        if ($this->sensorData->removeElement($sensorData) && $sensorData->getParcel() === $this) {
            $sensorData->setParcel(null);
        }
        return $this;
    }

    /** @return Collection<int, Treatment> */
    public function getTreatments(): Collection { return $this->treatments; }
    public function addTreatment(Treatment $treatment): static
    {
        if (!$this->treatments->contains($treatment)) {
            $this->treatments->add($treatment);
            $treatment->setParcel($this);
        }
        return $this;
    }
    public function removeTreatment(Treatment $treatment): static
    {
        if ($this->treatments->removeElement($treatment) && $treatment->getParcel() === $this) {
            $treatment->setParcel(null);
        }
        return $this;
    }

    /** @return Collection<int, CropOperation> */
    public function getCropOperations(): Collection { return $this->cropOperations; }
    public function addCropOperation(CropOperation $cropOperation): static
    {
        if (!$this->cropOperations->contains($cropOperation)) {
            $this->cropOperations->add($cropOperation);
            $cropOperation->setParcel($this);
        }
        return $this;
    }
    public function removeCropOperation(CropOperation $cropOperation): static
    {
        if ($this->cropOperations->removeElement($cropOperation) && $cropOperation->getParcel() === $this) {
            $cropOperation->setParcel(null);
        }
        return $this;
    }
}