<?php

namespace App\Tests\Service;

use App\Entity\Farm;
use App\Service\WeatherService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WeatherServiceTest extends TestCase
{
    private function makeFarm(float $lat = 37.63, float $lon = 13.63): Farm
    {
        $farm = new Farm();
        $farm->setLatitude($lat)
            ->setLongitude($lon)
            ->setName('Test Farm')
            ->setSurface(3.0)
            ->setSoilType('clay')
            ->setMunicipality('Cammarata')
            ->setAltitude(650);
        return $farm;
    }

    private function makeCache(): CacheInterface
    {
        return new \Symfony\Component\Cache\Adapter\ArrayAdapter();
    }

    public function testGetCurrentWeatherReturnsArray(): void
    {
        $payload = [
            'current' => [
                'temperature_2m'        => 22.5,
                'relative_humidity_2m'  => 65,
                'precipitation'         => 0.0,
                'wind_speed_10m'        => 12.3,
                'time'                  => '2025-05-21T10:00',
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($payload);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $cache   = $this->makeCache();
        $service = new WeatherService($client, $cache);
        $farm    = $this->makeFarm();

        $result = $service->getCurrentWeather($farm);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('temp', $result);
        $this->assertArrayHasKey('humidity', $result);
        $this->assertArrayHasKey('rain', $result);
        $this->assertArrayHasKey('wind', $result);
        $this->assertSame(22.5, $result['temp']);
        $this->assertSame(65, $result['humidity']);
    }

    public function testGetCurrentWeatherCachesResult(): void
    {
        $payload = [
            'current' => [
                'temperature_2m'       => 18.0,
                'relative_humidity_2m' => 55,
                'precipitation'        => 0.0,
                'wind_speed_10m'       => 5.0,
                'time'                 => '2025-05-21T12:00',
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($payload);

        $client = $this->createMock(HttpClientInterface::class);
        // HTTP is called only once — second call comes from cache
        $client->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $cache   = $this->makeCache();
        $service = new WeatherService($client, $cache);
        $farm    = $this->makeFarm();

        $first  = $service->getCurrentWeather($farm);
        $second = $service->getCurrentWeather($farm);

        $this->assertSame($first['temp'], $second['temp']);
    }
}