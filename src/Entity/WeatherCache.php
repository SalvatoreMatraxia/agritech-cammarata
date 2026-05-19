<?php

namespace App\Entity;

use App\Repository\WeatherCacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeatherCacheRepository::class)]
class WeatherCache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(length: 50)]
    private ?string $source = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $fetchedAt = null;

    public function __construct()
    {
        $this->fetchedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(float $latitude): static { $this->latitude = $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(float $longitude): static { $this->longitude = $longitude; return $this; }

    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }

    public function getData(): array { return $this->data; }
    public function setData(array $data): static { $this->data = $data; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(string $source): static { $this->source = $source; return $this; }

    public function getFetchedAt(): ?\DateTimeImmutable { return $this->fetchedAt; }
    public function setFetchedAt(\DateTimeImmutable $fetchedAt): static { $this->fetchedAt = $fetchedAt; return $this; }
}