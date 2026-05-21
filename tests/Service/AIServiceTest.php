<?php

namespace App\Tests\Service;

use App\Entity\Farm;
use App\Entity\Parcel;
use App\Entity\Prediction;
use App\Service\AIService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AIServiceTest extends TestCase
{
    private function makeFarm(): Farm
    {
        $farm = new Farm();
        $farm->setName('Test')
            ->setLatitude(37.63)
            ->setLongitude(13.63)
            ->setSurface(3.0)
            ->setSoilType('clay')
            ->setMunicipality('Cammarata')
            ->setAltitude(650);

        $parcel = new Parcel();
        $parcel->setName('Parcella Nord')
            ->setSurface(1.5)
            ->setTreeCount(250)
            ->setVariety('Nocellara del Belice')
            ->setPlantingYear(1990)
            ->setFarm($farm);
        $farm->addParcel($parcel);

        return $farm;
    }

    public function testPredictYieldSuccess(): void
    {
        $apiResponse = [
            'predicted_yield_kg' => 12500.0,
            'confidence'         => 0.87,
            'method'             => 'xgboost',
            'explanation_text'   => 'Good season.',
            'scenarios'          => null,
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($apiResponse);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(Prediction::class));
        $em->expects($this->once())->method('flush');

        $service = new AIService($client, $em, new NullLogger(), 'http://localhost:8001');

        $prediction = $service->predictYield($this->makeFarm(), 2025);

        $this->assertInstanceOf(Prediction::class, $prediction);
        $this->assertSame('yield', $prediction->getType());
        $this->assertSame('xgboost', $prediction->getMethod());
        $this->assertEqualsWithDelta(0.87, $prediction->getConfidence(), 0.001);
    }

    public function testPredictYieldFallbackOnError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = new AIService($client, $em, new NullLogger(), 'http://localhost:8001');

        $this->expectException(\RuntimeException::class);
        $service->predictYield($this->makeFarm(), 2025);
    }
}