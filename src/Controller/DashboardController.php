<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PhenologyService;
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
        private PhenologyService $phenologyService,
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

        $phenology = [];
        if ($farm !== null) {
            try {
                $phenology = $this->phenologyService->getCurrentPhenology($farm);
            } catch (\Throwable) {
                // non-blocking
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'farm'       => $farm,
            'weather'    => $weather,
            'forecast'   => $forecast,
            'totalTrees' => $totalTrees,
            'phenology'  => $phenology,
        ]);
    }
}