<?php

namespace App\Controller;

use App\Entity\Treatment;
use App\Entity\User;
use App\Repository\OrganicProductRepository;
use App\Repository\TreatmentRepository;
use App\Service\TreatmentEfficacyService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[IsGranted('ROLE_USER')]
#[Route('/treatments')]
class TreatmentController extends AbstractController
{
    public function __construct(
        private TreatmentRepository $treatmentRepository,
        private OrganicProductRepository $productRepository,
        private TreatmentEfficacyService $efficacyService,
        private EntityManagerInterface $em,
        private Environment $twig,
    ) {}

    #[Route('', name: 'treatment_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->render('treatment/index.html.twig', ['treatments' => [], 'farm' => null, 'efficacies' => [], 'page' => 1, 'pages' => 1, 'total' => 0]);
        }

        $page     = max(1, (int) $request->query->get('page', 1));
        $parcelId = $request->query->get('parcel') ? (int) $request->query->get('parcel') : null;
        $limit    = 20;

        $treatments = $this->treatmentRepository->findByFarm($farm, $page, $limit, $parcelId);
        $total      = $this->treatmentRepository->countByFarm($farm, $parcelId);

        $efficacies = [];
        foreach ($treatments as $t) {
            try {
                $efficacies[$t->getId()] = $t->getOrganicProduct()
                    ? $this->efficacyService->calculateCurrentEfficacy($t)
                    : null;
            } catch (\Throwable) {
                $efficacies[$t->getId()] = null;
            }
        }

        return $this->render('treatment/index.html.twig', [
            'farm'        => $farm,
            'treatments'  => $treatments,
            'efficacies'  => $efficacies,
            'page'        => $page,
            'pages'       => (int) ceil($total / $limit),
            'total'       => $total,
            'parcelId'    => $parcelId,
        ]);
    }

    #[Route('/new', name: 'treatment_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('treatment_index');
        }

        $products = $this->productRepository->findBy(['isAllowedOrganic' => true], ['commercialName' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('treatment_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token di sicurezza non valido. Riprova.');
                return $this->redirectToRoute('treatment_new');
            }

            $parcelId  = (int) $request->request->get('parcel_id');
            $productId = (int) $request->request->get('product_id');
            $parcel    = null;

            foreach ($farm->getParcels() as $p) {
                if ($p->getId() === $parcelId) {
                    $parcel = $p;
                    break;
                }
            }

            if ($parcel === null) {
                $this->addFlash('error', 'Parcella non valida.');
                return $this->redirectToRoute('treatment_new');
            }

            $product = $productId ? $this->productRepository->find($productId) : null;

            $treatment = new Treatment();
            $treatment
                ->setParcel($parcel)
                ->setOrganicProduct($product)
                ->setDate(new \DateTimeImmutable($request->request->get('date') ?: 'today'))
                ->setProductName($product ? $product->getCommercialName() : (string) $request->request->get('product_name'))
                ->setActiveSubstance($product ? $product->getActiveSubstance() : (string) $request->request->get('active_substance'))
                ->setDosePerHa((float) $request->request->get('dose_per_ha', 0))
                ->setDoseUnit((string) $request->request->get('dose_unit', 'kg/ha'))
                ->setTotalQuantity($request->request->get('total_quantity') !== '' ? (float) $request->request->get('total_quantity') : null)
                ->setTargetPest((string) $request->request->get('target_pest'))
                ->setApplicationMethod($request->request->get('application_method') ?: null)
                ->setWeatherConditions($request->request->get('weather_conditions') ?: null)
                ->setOperator($request->request->get('operator') ?: null)
                ->setIsOrganic($product ? $product->isIsAllowedOrganic() : false)
                ->setNotes($request->request->get('notes') ?: null)
                ->setRegisteredVia('web');

            $this->em->persist($treatment);
            $this->em->flush();

            $this->addFlash('success', 'Trattamento registrato nel Quaderno di Campagna.');
            return $this->redirectToRoute('treatment_index');
        }

        return $this->render('treatment/new.html.twig', [
            'farm'     => $farm,
            'products' => $products,
        ]);
    }

    #[Route('/export-pdf', name: 'treatment_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null) {
            return $this->redirectToRoute('treatment_index');
        }

        $dateFrom = $request->query->get('from') ? new \DateTimeImmutable($request->query->get('from')) : new \DateTimeImmutable('first day of january this year');
        $dateTo   = $request->query->get('to')   ? new \DateTimeImmutable($request->query->get('to'))   : new \DateTimeImmutable();

        $treatments = $this->treatmentRepository->findAllByFarmOrdered($farm);
        $treatments = array_filter($treatments, fn(Treatment $t) =>
            $t->getDate() >= $dateFrom && $t->getDate() <= $dateTo
        );

        $html = $this->twig->render('treatment/pdf.html.twig', [
            'farm'       => $farm,
            'treatments' => array_values($treatments),
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'generated'  => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('quaderno_campagna_%s_%s.pdf', $dateFrom->format('Ymd'), $dateTo->format('Ymd'));

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/{id}', name: 'treatment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Treatment $treatment): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $farm = $user->getFarms()->first() ?: null;

        if ($farm === null || $treatment->getParcel()->getFarm() !== $farm) {
            throw $this->createNotFoundException();
        }

        $efficacy = null;
        try {
            if ($treatment->getOrganicProduct()) {
                $efficacy = $this->efficacyService->calculateCurrentEfficacy($treatment);
            }
        } catch (\Throwable) {}

        return $this->render('treatment/show.html.twig', [
            'treatment' => $treatment,
            'efficacy'  => $efficacy,
        ]);
    }
}