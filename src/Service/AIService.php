<?php

namespace App\Service;

use App\Entity\Farm;
use App\Entity\Prediction;
use App\Repository\FarmSeasonRecordRepository;
use App\Repository\PredictionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private const MAX_PREDICTIONS_PER_TYPE = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private FarmSeasonRecordRepository $seasonRepo,
        private PredictionRepository $predictionRepo,
        private string $aiServiceUrl,
    ) {}

    public function predictYield(Farm $farm, int $year): Prediction
    {
        $treeCount   = 0;
        $variety     = null;
        $plantingYear = null;

        foreach ($farm->getParcels() as $parcel) {
            $treeCount += (int) $parcel->getTreeCount();
            if ($variety === null && $parcel->getVariety()) {
                $variety      = $parcel->getVariety();
                $plantingYear = $parcel->getPlantingYear();
            }
        }

        $variety ??= 'Nocellara del Belice';

        // ── dati storici per fattore alternanza ───────────────────────────────
        $records      = $this->seasonRepo->findBy(['farm' => $farm]);
        $totalYield   = 0.0;
        $recordCount  = 0;
        $prevYieldKg  = null;

        foreach ($records as $record) {
            if ($record->getActualYieldKg() !== null) {
                $totalYield  += $record->getActualYieldKg();
                $recordCount++;
                if ($record->getYear() === $year - 1) {
                    $prevYieldKg = $record->getActualYieldKg();
                }
            }
        }

        $farmAvgKg = $recordCount > 0 ? $totalYield / $recordCount : null;

        $data = $this->post('/predict/yield', [
            'latitude'            => $farm->getLatitude(),
            'longitude'           => $farm->getLongitude(),
            'surface_ha'          => $farm->getSurface(),
            'tree_count'          => $treeCount,
            'variety'             => $variety,
            'year'                => $year,
            'planting_year'       => $plantingYear,
            'prev_year_actual_kg' => $prevYieldKg,
            'farm_avg_kg'         => $farmAvgKg,
        ]);

        return $this->persist(
            farm:    $farm,
            type:    'yield',
            data:    $data,
            method:  $data['method'] ?? 'xgboost',
            target:  new \DateTimeImmutable("$year-12-31"),
            scenarios: $data['scenarios'] ?? null,
        );
    }

    public function getPestRisk(Farm $farm): Prediction
    {
        $data = $this->post('/predict/pest', [
            'latitude'        => $farm->getLatitude(),
            'longitude'       => $farm->getLongitude(),
            'farm_surface_ha' => $farm->getSurface(),
        ]);

        return $this->persist(
            farm:   $farm,
            type:   'pest_risk',
            data:   $data,
            method: 'biological_degree_day',
            target: new \DateTimeImmutable(),
        );
    }

    public function getWaterStress(Farm $farm): Prediction
    {
        $data = $this->post('/predict/water-stress', [
            'latitude'   => $farm->getLatitude(),
            'longitude'  => $farm->getLongitude(),
            'soil_type'  => $farm->getSoilType(),
            'surface_ha' => $farm->getSurface(),
        ]);

        return $this->persist(
            farm:       $farm,
            type:       'water_stress',
            data:       $data,
            method:     'fao56',
            target:     new \DateTimeImmutable(),
            confidence: 0.85,
        );
    }

    private function post(string $path, array $body): array
    {
        $response = $this->httpClient->request('POST', $this->aiServiceUrl . $path, [
            'json'    => $body,
            'timeout' => 15,
        ]);

        return $response->toArray();
    }

    private function persist(
        Farm $farm,
        string $type,
        array $data,
        string $method,
        \DateTimeImmutable $target,
        ?float $confidence = null,
        ?array $scenarios = null,
    ): Prediction {
        $this->pruneOldPredictions($farm, $type);

        $prediction = (new Prediction())
            ->setFarm($farm)
            ->setType($type)
            ->setResult($data)
            ->setConfidence($confidence ?? (float) ($data['confidence'] ?? 0.5))
            ->setMethod($method)
            ->setExplanationText($data['explanation_text'] ?? null)
            ->setScenarios($scenarios)
            ->setTargetDate($target);

        $this->em->persist($prediction);
        $this->em->flush();

        return $prediction;
    }

    private function pruneOldPredictions(Farm $farm, string $type): void
    {
        $all = $this->predictionRepo->findBy(
            ['farm' => $farm, 'type' => $type],
            ['createdAt' => 'DESC'],
        );

        $toDelete = array_slice($all, self::MAX_PREDICTIONS_PER_TYPE - 1);
        foreach ($toDelete as $old) {
            $this->em->remove($old);
        }
    }
}