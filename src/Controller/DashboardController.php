<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PredictionRepository;
use App\Service\AIService;
use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private WeatherService $weatherService,
        private AIService $aiService,
        private PredictionRepository $predictionRepository,
    ) {}

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('dashboard_index');
    }

    #[Route('/dashboard', name: 'dashboard_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user     = $this->getUser();
        $farm     = $user->getFarms()->first() ?: null;
        $weather  = null;
        $forecast = [];

        if ($farm !== null) {
            try {
                $weather  = $this->weatherService->getCurrentWeather($farm);
                $forecast = $this->weatherService->getForecast($farm, 7);
            } catch (\Throwable) {
                $this->addFlash('warning', 'Dati meteo temporaneamente non disponibili.');
            }
        }

        $totalTrees = 0;
        if ($farm !== null) {
            foreach ($farm->getParcels() as $parcel) {
                $totalTrees += $parcel->getTreeCount();
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'farm'            => $farm,
            'weather'         => $weather,
            'forecast'        => $forecast,
            'totalTrees'      => $totalTrees,
            'yieldPrediction' => $farm ? $this->predictionRepository->findLatestByType($farm, 'yield') : null,
            'pestPrediction'  => $farm ? $this->predictionRepository->findLatestByType($farm, 'pest_risk') : null,
            'waterPrediction' => $farm ? $this->predictionRepository->findLatestByType($farm, 'water_stress') : null,
        ]);
    }

    #[Route('/dashboard/predict/yield', name: 'dashboard_predict_yield', methods: ['POST'])]
    public function predictYield(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('dashboard_index');
        }

        try {
            $this->aiService->predictYield($farm, (int) date('Y'));
            $this->addFlash('success', 'Previsione resa aggiornata con successo.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('dashboard_index');
    }

    #[Route('/dashboard/predict/pest', name: 'dashboard_predict_pest', methods: ['POST'])]
    public function predictPest(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('dashboard_index');
        }

        try {
            $this->aiService->getPestRisk($farm);
            $this->addFlash('success', 'Analisi rischio mosca olearia aggiornata.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('dashboard_index');
    }

    #[Route('/dashboard/predict/water', name: 'dashboard_predict_water', methods: ['POST'])]
    public function predictWater(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('dashboard_index');
        }

        try {
            $this->aiService->getWaterStress($farm);
            $this->addFlash('success', 'Bilancio idrico aggiornato.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'Servizio AI non disponibile. Avvia il microservizio Python e riprova.');
        }

        return $this->redirectToRoute('dashboard_index');
    }
}