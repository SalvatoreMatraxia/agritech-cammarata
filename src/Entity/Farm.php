<?php

namespace App\Entity;

use App\Repository\FarmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FarmRepository::class)]
class Farm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\Column]
    private ?float $surface = null;

    #[ORM\Column(length: 50)]
    private ?string $soilType = null;

    #[ORM\Column(length: 100)]
    private ?string $municipality = null;

    #[ORM\Column]
    private ?int $altitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $distanceSeaKm = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'farms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Parcel::class, mappedBy: 'farm', cascade: ['persist', 'remove'])]
    private Collection $parcels;

    #[ORM\OneToMany(targetEntity: Prediction::class, mappedBy: 'farm', cascade: ['persist', 'remove'])]
    private Collection $predictions;

    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'farm', cascade: ['persist', 'remove'])]
    private Collection $alerts;

    #[ORM\OneToMany(targetEntity: FarmSeasonRecord::class, mappedBy: 'farm', cascade: ['persist', 'remove'])]
    private Collection $farmSeasonRecords;

    #[ORM\OneToOne(targetEntity: EconomicParams::class, mappedBy: 'farm', cascade: ['persist', 'remove'])]
    private ?EconomicParams $economicParams = null;

    public function __construct()
    {
        $this->parcels = new ArrayCollection();
        $this->predictions = new ArrayCollection();
        $this->alerts = new ArrayCollection();
        $this->farmSeasonRecords = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(float $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(float $longitude): static { $this->longitude = $longitude; return $this; }

    public function getSurface(): ?float { return $this->surface; }
    public function setSurface(float $surface): static { $this->surface = $surface; return $this; }

    public function getSoilType(): ?string { return $this->soilType; }
    public function setSoilType(string $soilType): static { $this->soilType = $soilType; return $this; }

    public function getMunicipality(): ?string { return $this->municipality; }
    public function setMunicipality(string $municipality): static { $this->municipality = $municipality; return $this; }

    public function getAltitude(): ?int { return $this->altitude; }
    public function setAltitude(int $altitude): static { $this->altitude = $altitude; return $this; }

    public function getDistanceSeaKm(): ?float { return $this->distanceSeaKm; }
    public function setDistanceSeaKm(?float $distanceSeaKm): static { $this->distanceSeaKm = $distanceSeaKm; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    /** @return Collection<int, Parcel> */
    public function getParcels(): Collection { return $this->parcels; }
    public function addParcel(Parcel $parcel): static
    {
        if (!$this->parcels->contains($parcel)) {
            $this->parcels->add($parcel);
            $parcel->setFarm($this);
        }
        return $this;
    }
    public function removeParcel(Parcel $parcel): static
    {
        if ($this->parcels->removeElement($parcel) && $parcel->getFarm() === $this) {
            $parcel->setFarm(null);
        }
        return $this;
    }

    /** @return Collection<int, Prediction> */
    public function getPredictions(): Collection { return $this->predictions; }
    public function addPrediction(Prediction $prediction): static
    {
        if (!$this->predictions->contains($prediction)) {
            $this->predictions->add($prediction);
            $prediction->setFarm($this);
        }
        return $this;
    }
    public function removePrediction(Prediction $prediction): static
    {
        if ($this->predictions->removeElement($prediction) && $prediction->getFarm() === $this) {
            $prediction->setFarm(null);
        }
        return $this;
    }

    /** @return Collection<int, Alert> */
    public function getAlerts(): Collection { return $this->alerts; }
    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setFarm($this);
        }
        return $this;
    }
    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert) && $alert->getFarm() === $this) {
            $alert->setFarm(null);
        }
        return $this;
    }

    /** @return Collection<int, FarmSeasonRecord> */
    public function getFarmSeasonRecords(): Collection { return $this->farmSeasonRecords; }
    public function addFarmSeasonRecord(FarmSeasonRecord $record): static
    {
        if (!$this->farmSeasonRecords->contains($record)) {
            $this->farmSeasonRecords->add($record);
            $record->setFarm($this);
        }
        return $this;
    }
    public function removeFarmSeasonRecord(FarmSeasonRecord $record): static
    {
        if ($this->farmSeasonRecords->removeElement($record) && $record->getFarm() === $this) {
            $record->setFarm(null);
        }
        return $this;
    }

    public function getEconomicParams(): ?EconomicParams { return $this->economicParams; }
    public function setEconomicParams(?EconomicParams $economicParams): static
    {
        if ($economicParams === null && $this->economicParams !== null) {
            $this->economicParams->setFarm(null);
        }
        if ($economicParams !== null && $economicParams->getFarm() !== $this) {
            $economicParams->setFarm($this);
        }
        $this->economicParams = $economicParams;
        return $this;
    }
}