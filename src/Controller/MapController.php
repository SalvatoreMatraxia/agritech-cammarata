<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SatelliteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MapController extends AbstractController
{
    public function __construct(private SatelliteService $satelliteService) {}

    #[Route('/map', name: 'map_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        $farmNdvi    = null;
        $parcelNdvi  = [];

        if ($farm !== null) {
            try {
                $farmNdvi   = $this->satelliteService->getNDVI($farm);
                $parcelNdvi = $this->satelliteService->getNDVIPerParcel($farm);
            } catch (\Throwable) {
                $this->addFlash('warning', 'Dati NDVI non disponibili. Avvia il microservizio AI.');
            }
        }

        $parcelsData = [];
        if ($farm !== null) {
            foreach ($farm->getParcels() as $parcel) {
                $ndvi = $parcelNdvi[$parcel->getId()] ?? null;
                $parcelsData[] = [
                    'id'         => $parcel->getId(),
                    'name'       => $parcel->getName(),
                    'variety'    => $parcel->getVariety(),
                    'surface'    => $parcel->getSurface(),
                    'treeCount'  => $parcel->getTreeCount(),
                    'aspect'     => $parcel->getAspect(),
                    'altitude'   => $parcel->getAltitude(),
                    'lat'        => $farm->getLatitude()  + $this->aspectLatOffset($parcel->getAspect()),
                    'lon'        => $farm->getLongitude() + $this->aspectLonOffset($parcel->getAspect()),
                    'ndvi'       => $ndvi ? round($ndvi['ndvi_current'], 3) : null,
                    'ndvi_trend' => $ndvi['ndvi_trend'] ?? null,
                    'anomaly'    => $ndvi['anomaly'] ?? false,
                    'history'    => $ndvi ? array_slice($ndvi['ndvi_history'], -30) : [],
                ];
            }
        }

        return $this->render('map/index.html.twig', [
            'farm'        => $farm,
            'farm_ndvi'   => $farmNdvi,
            'parcels'     => $parcelsData,
        ]);
    }

    private function aspectLatOffset(?string $aspect): float
    {
        return match (strtoupper((string) $aspect)) {
            'N', 'NE', 'NW' => 0.001,
            'S', 'SE', 'SW' => -0.001,
            default          => 0.0,
        };
    }

    private function aspectLonOffset(?string $aspect): float
    {
        return match (strtoupper((string) $aspect)) {
            'E', 'NE', 'SE' => 0.001,
            'W', 'NW', 'SW' => -0.001,
            default          => 0.0,
        };
    }
}