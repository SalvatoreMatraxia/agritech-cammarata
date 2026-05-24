<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PredictionRepository;
use App\Service\AIService;
use App\Service\SatelliteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/monitoring')]
class MonitoringController extends AbstractController
{
    public function __construct(
        private AIService $aiService,
        private SatelliteService $satelliteService,
        private PredictionRepository $predictionRepository,
    ) {}

    #[Route('', name: 'monitoring_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;
        $ndvi = null;

        if ($farm !== null) {
            try {
                $ndvi = $this->satelliteService->getNDVI($farm);
            } catch (\Throwable) {
                // non-blocking
            }
        }

        return $this->render('monitoring/index.html.twig', [
            'farm'            => $farm,
            'ndvi'            => $ndvi,
            'yieldPrediction' => $farm ? $this->predictionRepository->findLatestByType($farm, 'yield') : null,
            'pestPrediction'  => $farm ? $this->predictionRepository->findLatestByType($farm, 'pest_risk') : null,
            'waterPrediction' => $farm ? $this->predictionRepository->findLatestByType($farm, 'water_stress') : null,
        ]);
    }

    #[Route('/predict/yield', name: 'monitoring_predict_yield', methods: ['POST'])]
    public function predictYield(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('monitoring_index');
        }

        try {
            $this->aiService->predictYield($farm, (int) date('Y'));
            $this->addFlash('success', 'Previsione resa aggiornata con successo.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('monitoring_index');
    }

    #[Route('/predict/pest', name: 'monitoring_predict_pest', methods: ['POST'])]
    public function predictPest(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('monitoring_index');
        }

        try {
            $this->aiService->getPestRisk($farm);
            $this->addFlash('success', 'Analisi rischio mosca olearia aggiornata.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('monitoring_index');
    }

    #[Route('/predict/water', name: 'monitoring_predict_water', methods: ['POST'])]
    public function predictWater(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('monitoring_index');
        }

        try {
            $this->aiService->getWaterStress($farm);
            $this->addFlash('success', 'Bilancio idrico aggiornato.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('monitoring_index');
    }
}