<?php

namespace App\Controller;

use App\Entity\FarmSeasonRecord;
use App\Entity\User;
use App\Repository\FarmSeasonRecordRepository;
use App\Repository\PredictionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/farm/memory')]
class FarmMemoryController extends AbstractController
{
    public function __construct(
        private FarmSeasonRecordRepository $recordRepository,
        private PredictionRepository $predictionRepository,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'farm_memory_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        $records = $farm
            ? $this->recordRepository->findBy(['farm' => $farm], ['year' => 'DESC'])
            : [];

        return $this->render('farm/memory.html.twig', [
            'farm'    => $farm,
            'records' => $records,
        ]);
    }

    #[Route('/new', name: 'farm_memory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            $this->addFlash('error', 'Nessuna azienda configurata.');
            return $this->redirectToRoute('farm_memory_index');
        }

        $currentYear = (int) date('Y');

        $latestYieldPrediction = $this->predictionRepository
            ->createQueryBuilder('p')
            ->where('p.farm = :farm')
            ->andWhere('p.type = :type')
            ->andWhere('YEAR(p.targetDate) = :year')
            ->setParameter('farm', $farm)
            ->setParameter('type', 'yield')
            ->setParameter('year', $currentYear)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $predictedYield = null;
        if ($latestYieldPrediction !== null) {
            $result = $latestYieldPrediction->getResult();
            $predictedYield = $result['predicted_yield_kg'] ?? $result['yield_kg'] ?? null;
        }

        if ($request->isMethod('POST')) {
            $year           = (int) $request->request->get('year', $currentYear);
            $actualYieldKg  = $request->request->get('actualYieldKg') !== ''
                ? (float) $request->request->get('actualYieldKg') : null;
            $actualOilLiters = $request->request->get('actualOilLiters') !== ''
                ? (float) $request->request->get('actualOilLiters') : null;
            $oilQuality     = $request->request->get('oilQuality') ?: null;
            $notes          = $request->request->get('notes') ?: null;
            $predicted      = $request->request->get('predictedYieldKg') !== ''
                ? (float) $request->request->get('predictedYieldKg') : null;

            $errorPct = null;
            if ($predicted !== null && $predicted > 0 && $actualYieldKg !== null) {
                $errorPct = round(abs($actualYieldKg - $predicted) / $predicted * 100, 1);
            }

            $record = (new FarmSeasonRecord())
                ->setFarm($farm)
                ->setYear($year)
                ->setSeason((string) $year)
                ->setActualYieldKg($actualYieldKg)
                ->setActualOilLiters($actualOilLiters)
                ->setOilQuality($oilQuality)
                ->setNotes($notes)
                ->setPredictedYieldKg($predicted)
                ->setPredictionErrorPct($errorPct);

            $this->em->persist($record);
            $this->em->flush();

            $this->addFlash('success', "Dati stagione $year salvati.");
            return $this->redirectToRoute('farm_memory_index');
        }

        return $this->render('farm/memory_new.html.twig', [
            'farm'           => $farm,
            'currentYear'    => $currentYear,
            'predictedYield' => $predictedYield,
        ]);
    }
}