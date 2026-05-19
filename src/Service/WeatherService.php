<?php

namespace App\Service;

use App\Entity\Farm;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private const FORECAST_URL = 'https://api.open-meteo.com/v1/forecast';
    private const ARCHIVE_URL  = 'https://archive-api.open-meteo.com/v1/archive';

    public function __construct(
        private HttpClientInterface $client,
        private CacheInterface $cache,
    ) {}

    public function getCurrentWeather(Farm $farm): array
    {
        $key = $this->cacheKey('current', $farm);

        return $this->cache->get($key, function (ItemInterface $item) use ($farm): array {
            $item->expiresAfter(3600);

            $response = $this->client->request('GET', self::FORECAST_URL, [
                'query' => [
                    'latitude'  => $farm->getLatitude(),
                    'longitude' => $farm->getLongitude(),
                    'current'   => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m',
                    'timezone'  => 'auto',
                ],
            ]);

            $data    = $response->toArray();
            $current = $data['current'] ?? [];

            return [
                'temp'     => $current['temperature_2m'] ?? null,
                'humidity' => $current['relative_humidity_2m'] ?? null,
                'rain'     => $current['precipitation'] ?? null,
                'wind'     => $current['wind_speed_10m'] ?? null,
                'time'     => $current['time'] ?? null,
            ];
        });
    }

    public function getForecast(Farm $farm, int $days = 14): array
    {
        $key = $this->cacheKey('forecast', $farm, (string) $days);

        return $this->cache->get($key, function (ItemInterface $item) use ($farm, $days): array {
            $item->expiresAfter(21600);

            $response = $this->client->request('GET', self::FORECAST_URL, [
                'query' => [
                    'latitude'     => $farm->getLatitude(),
                    'longitude'    => $farm->getLongitude(),
                    'daily'        => 'temperature_2m_max,temperature_2m_min,precipitation_sum,et0_fao_evapotranspiration',
                    'forecast_days' => $days,
                    'timezone'     => 'auto',
                ],
            ]);

            $data  = $response->toArray();
            $daily = $data['daily'] ?? [];
            $rows  = [];

            foreach (($daily['time'] ?? []) as $i => $date) {
                $rows[] = [
                    'date'     => $date,
                    'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                    'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                    'rain'     => $daily['precipitation_sum'][$i] ?? null,
                    'et0'      => $daily['et0_fao_evapotranspiration'][$i] ?? null,
                ];
            }

            return $rows;
        });
    }

    public function getHistorical(Farm $farm, int $daysBack = 30): array
    {
        $today     = new \DateTimeImmutable();
        $startDate = $today->modify("-{$daysBack} days")->format('Y-m-d');
        $endDate   = $today->format('Y-m-d');
        $key       = $this->cacheKey('historical', $farm, $startDate);

        return $this->cache->get($key, function (ItemInterface $item) use ($farm, $startDate, $endDate): array {
            $item->expiresAfter(86400);

            $response = $this->client->request('GET', self::ARCHIVE_URL, [
                'query' => [
                    'latitude'   => $farm->getLatitude(),
                    'longitude'  => $farm->getLongitude(),
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'daily'      => 'temperature_2m_max,temperature_2m_min,precipitation_sum,et0_fao_evapotranspiration,relative_humidity_2m_mean',
                    'timezone'   => 'auto',
                ],
            ]);

            $data  = $response->toArray();
            $daily = $data['daily'] ?? [];
            $rows  = [];

            foreach (($daily['time'] ?? []) as $i => $date) {
                $rows[] = [
                    'date'     => $date,
                    'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                    'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                    'rain'     => $daily['precipitation_sum'][$i] ?? null,
                    'et0'      => $daily['et0_fao_evapotranspiration'][$i] ?? null,
                    'humidity' => $daily['relative_humidity_2m_mean'][$i] ?? null,
                ];
            }

            return $rows;
        });
    }

    private function cacheKey(string $type, Farm $farm, string $extra = ''): string
    {
        $lat = str_replace('.', '_', (string) round($farm->getLatitude(), 3));
        $lon = str_replace('.', '_', (string) round($farm->getLongitude(), 3));
        $suffix = $extra !== '' ? '_' . preg_replace('/[^a-z0-9_\-]/i', '_', $extra) : '';

        return "weather_{$type}_{$lat}_{$lon}{$suffix}";
    }
}