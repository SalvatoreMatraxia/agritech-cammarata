<?php

namespace App\Controller;

use App\Entity\CropOperation;
use App\Entity\User;
use App\Repository\CropOperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/operations')]
class CropOperationController extends AbstractController
{
    public const TYPES = [
        'potatura'           => ['label' => 'Potatura',            'icon' => 'scissors'],
        'irrigazione'        => ['label' => 'Irrigazione',         'icon' => 'droplets'],
        'raccolta'           => ['label' => 'Raccolta',            'icon' => 'shopping-basket'],
        'concimazione'       => ['label' => 'Concimazione',        'icon' => 'sprout'],
        'monitoraggio'       => ['label' => 'Monitoraggio',        'icon' => 'search'],
        'lavorazione_terreno'=> ['label' => 'Lavorazione terreno', 'icon' => 'wrench'],
    ];

    public function __construct(
        private CropOperationRepository $operationRepository,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'operation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->render('operation/index.html.twig', ['operations' => [], 'farm' => null, 'page' => 1, 'pages' => 1, 'total' => 0]);
        }

        $page  = max(1, (int) $request->query->get('page', 1));
        $type  = $request->query->get('type') ?: null;
        $limit = 20;

        $operations = $this->operationRepository->findByFarm($farm, $page, $limit, $type);
        $total      = $this->operationRepository->countByFarm($farm, $type);

        return $this->render('operation/index.html.twig', [
            'farm'       => $farm,
            'operations' => $operations,
            'types'      => self::TYPES,
            'activeType' => $type,
            'page'       => $page,
            'pages'      => (int) ceil($total / $limit),
            'total'      => $total,
        ]);
    }

    #[Route('/new', name: 'operation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('operation_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('operation_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token di sicurezza non valido. Riprova.');
                return $this->redirectToRoute('operation_new');
            }

            $parcelId = (int) $request->request->get('parcel_id');
            $parcel   = null;

            foreach ($farm->getParcels() as $p) {
                if ($p->getId() === $parcelId) {
                    $parcel = $p;
                    break;
                }
            }

            if ($parcel === null) {
                $this->addFlash('error', 'Parcella non valida.');
                return $this->redirectToRoute('operation_new');
            }

            $operation = new CropOperation();
            $operation
                ->setParcel($parcel)
                ->setDate(new \DateTimeImmutable($request->request->get('date') ?: 'today'))
                ->setType((string) $request->request->get('type'))
                ->setDescription($request->request->get('description') ?: null)
                ->setDurationHours($request->request->get('duration_hours') !== '' ? (float) $request->request->get('duration_hours') : null)
                ->setOperator($request->request->get('operator') ?: null)
                ->setCostEur($request->request->get('cost_eur') !== '' ? (float) $request->request->get('cost_eur') : null)
                ->setNotes($request->request->get('notes') ?: null);

            $this->em->persist($operation);
            $this->em->flush();

            $this->addFlash('success', 'Operazione colturale registrata con successo.');
            return $this->redirectToRoute('operation_index');
        }

        return $this->render('operation/new.html.twig', [
            'farm'  => $farm,
            'types' => self::TYPES,
        ]);
    }
}