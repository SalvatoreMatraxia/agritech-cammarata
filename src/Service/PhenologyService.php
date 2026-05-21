<?php

namespace App\Service;

use App\Entity\Farm;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhenologyService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $aiServiceUrl,
    ) {}

    /**
     * Returns phenological stage for each parcel, indexed by parcel ID.
     * Results are cached for 24 hours (phenology changes slowly).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCurrentPhenology(Farm $farm): array
    {
        $results = [];

        foreach ($farm->getParcels() as $parcel) {
            $variety  = $parcel->getVariety() ?: 'Nocellara del Belice';
            $cacheKey = sprintf(
                'pheno_%d_%d_%s',
                (int) ($farm->getLatitude()  * 100),
                (int) ($farm->getLongitude() * 100),
                preg_replace('/[^a-z0-9]/', '_', strtolower($variety))
            );

            try {
                $data = $this->cache->get(
                    $cacheKey,
                    function (ItemInterface $item) use ($farm, $variety): array {
                        $item->expiresAfter(86400);
                        return $this->fetch($farm->getLatitude(), $farm->getLongitude(), $variety);
                    }
                );

                // Inject parcel metadata so the template can display names
                $data['parcel_id']   = $parcel->getId();
                $data['parcel_name'] = $parcel->getName();

                $results[$parcel->getId()] = $data;
            } catch (\Throwable) {
                // AI service offline — skip this parcel silently
            }
        }

        return $results;
    }

    private function fetch(float $lat, float $lon, string $variety): array
    {
        $response = $this->httpClient->request('GET', $this->aiServiceUrl . '/phenology/current', [
            'query'   => ['lat' => $lat, 'lon' => $lon, 'variety' => $variety],
            'timeout' => 20,
        ]);

        return $response->toArray();
    }
}