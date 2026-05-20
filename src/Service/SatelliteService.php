<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Farm;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SatelliteService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private string $aiServiceUrl,
    ) {}

    public function getNDVI(Farm $farm): array
    {
        $lat = $farm->getLatitude();
        $lon = $farm->getLongitude();
        $key = $this->cacheKey($lat, $lon);

        return $this->cache->get($key, function (ItemInterface $item) use ($lat, $lon): array {
            $item->expiresAfter(86400); // 24h — NDVI updates every ~5 days

            $response = $this->httpClient->request('GET', $this->aiServiceUrl . '/satellite/ndvi', [
                'query'   => ['lat' => $lat, 'lon' => $lon, 'days' => 90],
                'timeout' => 30,
            ]);

            return $response->toArray();
        });
    }

    public function getNDVIPerParcel(Farm $farm): array
    {
        $results = [];

        foreach ($farm->getParcels() as $parcel) {
            // Offset coordinates slightly per parcel aspect so each parcel gets its own data point
            [$lat, $lon] = $this->parcelCoordinates($farm, $parcel->getAspect());
            $key = $this->cacheKey($lat, $lon);

            try {
                $data = $this->cache->get($key, function (ItemInterface $item) use ($lat, $lon): array {
                    $item->expiresAfter(86400);

                    $response = $this->httpClient->request('GET', $this->aiServiceUrl . '/satellite/ndvi', [
                        'query'   => ['lat' => $lat, 'lon' => $lon, 'days' => 90],
                        'timeout' => 30,
                    ]);

                    return $response->toArray();
                });

                $results[$parcel->getId()] = $data;
            } catch (\Throwable $e) {
                $this->logger->warning('NDVI fetch failed for parcel {id}: {msg}', [
                    'id'  => $parcel->getId(),
                    'msg' => $e->getMessage(),
                ]);
                $results[$parcel->getId()] = null;
            }
        }

        return $results;
    }

    public function checkNDVIAnomaly(Farm $farm): ?Alert
    {
        try {
            $data = $this->getNDVI($farm);
        } catch (\Throwable) {
            return null;
        }

        if (!($data['anomaly'] ?? false)) {
            return null;
        }

        $alert = new Alert();
        $alert->setFarm($farm)
            ->setType('ndvi_anomaly')
            ->setSeverity('medium')
            ->setMessage($data['anomaly_description'] ?? 'Anomalia NDVI rilevata — verificare stato vegetazione.')
            ->setIsRead(false)
            ->setSentWhatsapp(false)
            ->setIsExtremeEvent(false);

        $this->em->persist($alert);
        $this->em->flush();

        return $alert;
    }

    private function parcelCoordinates(Farm $farm, ?string $aspect): array
    {
        $lat = $farm->getLatitude();
        $lon = $farm->getLongitude();

        // ~100m offset per aspect so each parcel gets a distinct API call/cache key
        $delta = 0.001;
        return match (strtoupper((string) $aspect)) {
            'N'  => [$lat + $delta, $lon],
            'S'  => [$lat - $delta, $lon],
            'E'  => [$lat, $lon + $delta],
            'W'  => [$lat, $lon - $delta],
            'NE' => [$lat + $delta, $lon + $delta],
            'NW' => [$lat + $delta, $lon - $delta],
            'SE' => [$lat - $delta, $lon + $delta],
            'SW' => [$lat - $delta, $lon - $delta],
            default => [$lat, $lon],
        };
    }

    private function cacheKey(float $lat, float $lon): string
    {
        $la = str_replace(['.', '-'], ['_', 'n'], (string) round($lat, 3));
        $lo = str_replace(['.', '-'], ['_', 'n'], (string) round($lon, 3));
        return "ndvi_{$la}_{$lo}";
    }
}