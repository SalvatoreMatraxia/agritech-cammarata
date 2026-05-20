<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Farm;
use App\Entity\Treatment;
use App\Repository\TreatmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class TreatmentEfficacyService
{
    public function __construct(
        private WeatherService $weatherService,
        private TreatmentRepository $treatmentRepository,
        private EntityManagerInterface $em,
    ) {}

    public function calculateCurrentEfficacy(Treatment $treatment): float
    {
        $product = $treatment->getOrganicProduct();
        if ($product === null) {
            return 0.0;
        }

        $farm        = $treatment->getParcel()->getFarm();
        $treatDate   = $treatment->getDate();
        $today       = new \DateTimeImmutable();
        $days        = (int) $treatDate->diff($today)->days;

        if ($days < 0) {
            return 100.0;
        }

        $halfLife    = $product->getHalfLifeDays();
        $washOffMm   = $product->getWashOffMm();

        $rainCumulated = 0.0;
        $tempSum       = 0.0;
        $tempDays      = 0;

        try {
            $historical = $this->weatherService->getHistorical($farm, max(1, $days + 1));

            foreach ($historical as $row) {
                if ($row['date'] < $treatDate->format('Y-m-d')) {
                    continue;
                }
                $rainCumulated += (float) ($row['rain'] ?? 0);
                $tmax = (float) ($row['temp_max'] ?? 25);
                $tmin = (float) ($row['temp_min'] ?? 15);
                $tempSum += ($tmax + $tmin) / 2;
                $tempDays++;
            }
        } catch (\Throwable) {
            // If weather API unavailable, compute only time decay
        }

        $tempAvg = $tempDays > 0 ? $tempSum / $tempDays : 20.0;

        // Half-life time decay
        $timeFactor = $days === 0 ? 1.0 : exp(-log(2) / $halfLife * $days);

        // Wash-off factor: each 10% of wash_off threshold removes proportional efficacy
        $washFactor = max(0.0, 1.0 - ($rainCumulated / $washOffMm) * 0.10);

        // Temperature modulation: optimal at 20°C ±1% per degree
        $tempFactor = 1.0 + ($tempAvg - 20.0) * 0.01;
        $tempFactor = max(0.5, min(1.5, $tempFactor));

        $efficacy = 100.0 * $timeFactor * $washFactor * $tempFactor;

        return round(max(0.0, min(100.0, $efficacy)), 1);
    }

    /** @return array<array{treatment: Treatment, efficacy: float, alert: bool}> */
    public function checkAllTreatments(Farm $farm): array
    {
        $treatments = $this->treatmentRepository->findRecentByFarm($farm, 30);
        $results    = [];

        foreach ($treatments as $treatment) {
            if ($treatment->getOrganicProduct() === null) {
                continue;
            }

            $efficacy  = $this->calculateCurrentEfficacy($treatment);
            $threshold = $treatment->getOrganicProduct()->getReTreatmentThreshold() * 100;
            $needsAlert = $efficacy < $threshold;

            if ($needsAlert) {
                $this->createReTreatmentAlert($farm, $treatment, $efficacy, $threshold);
            }

            $results[] = [
                'treatment' => $treatment,
                'efficacy'  => $efficacy,
                'alert'     => $needsAlert,
            ];
        }

        return $results;
    }

    private function createReTreatmentAlert(Farm $farm, Treatment $treatment, float $efficacy, float $threshold): void
    {
        $alert = new Alert();
        $alert->setFarm($farm)
            ->setType('treatment_efficacy')
            ->setSeverity('warning')
            ->setMessage(sprintf(
                'Efficacia %s in calo: %.0f%% (soglia: %.0f%%). Valutare ritrattamento su %s.',
                $treatment->getProductName(),
                $efficacy,
                $threshold,
                $treatment->getParcel()->getName(),
            ))
            ->setIsRead(false)
            ->setSentWhatsapp(false)
            ->setIsExtremeEvent(false);

        $this->em->persist($alert);
        $this->em->flush();
    }
}