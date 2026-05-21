<?php

namespace App\Tests\Service;

use App\Entity\Farm;
use App\Entity\OrganicProduct;
use App\Entity\Parcel;
use App\Entity\Treatment;
use App\Repository\TreatmentRepository;
use App\Service\TreatmentEfficacyService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TreatmentEfficacyServiceTest extends TestCase
{
    private function buildTreatment(\DateTimeImmutable $date): Treatment
    {
        $product = new OrganicProduct();
        $product->setCommercialName('Spinosad Test')
            ->setActiveSubstance('spinosad')
            ->setHalfLifeDays(7)
            ->setWashOffMm(20.0)
            ->setReTreatmentThreshold(0.30);

        $farm = new Farm();
        $farm->setName('Test Farm')
            ->setLatitude(37.63)
            ->setLongitude(13.63)
            ->setSurface(3.0)
            ->setSoilType('clay')
            ->setMunicipality('Cammarata')
            ->setAltitude(650);

        $parcel = new Parcel();
        $parcel->setName('Nord')
            ->setSurface(1.5)
            ->setTreeCount(250)
            ->setVariety('Nocellara')
            ->setPlantingYear(1995)
            ->setFarm($farm);

        $treatment = new Treatment();
        $treatment->setDate($date)
            ->setProductName('Spinosad Test')
            ->setActiveSubstance('spinosad')
            ->setDosePerHa(0.75)
            ->setDoseUnit('L/ha')
            ->setTargetPest('Bactrocera oleae')
            ->setParcel($parcel)
            ->setOrganicProduct($product);

        return $treatment;
    }

    public function testCalculateEfficacyNewTreatment(): void
    {
        $today = new \DateTimeImmutable('today');
        $treatment = $this->buildTreatment($today);

        $weatherService = $this->createMock(WeatherService::class);
        $weatherService->method('getHistorical')->willReturn([]);

        $repo = $this->createMock(TreatmentRepository::class);
        $em   = $this->createMock(EntityManagerInterface::class);

        $service  = new TreatmentEfficacyService($weatherService, $repo, $em);
        $efficacy = $service->calculateCurrentEfficacy($treatment);

        // Nuovo trattamento: efficacia vicino a 100%
        $this->assertGreaterThanOrEqual(90.0, $efficacy);
        $this->assertLessThanOrEqual(100.0, $efficacy);
    }

    public function testCalculateEfficacyDecayed(): void
    {
        $past      = new \DateTimeImmutable('-15 days');
        $treatment = $this->buildTreatment($past);

        $weatherService = $this->createMock(WeatherService::class);
        $weatherService->method('getHistorical')->willReturn([]);

        $repo = $this->createMock(TreatmentRepository::class);
        $em   = $this->createMock(EntityManagerInterface::class);

        $service  = new TreatmentEfficacyService($weatherService, $repo, $em);
        $efficacy = $service->calculateCurrentEfficacy($treatment);

        // Dopo 15 gg con halfLife=7: exp(-ln2/7*15) ≈ 0.23 → < 50%
        $this->assertLessThan(50.0, $efficacy);
        $this->assertGreaterThanOrEqual(0.0, $efficacy);
    }
}