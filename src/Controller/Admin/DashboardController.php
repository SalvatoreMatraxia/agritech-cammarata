<?php

namespace App\Controller\Admin;

use App\Entity\Alert;
use App\Entity\Farm;
use App\Entity\FarmSeasonRecord;
use App\Entity\Parcel;
use App\Entity\Prediction;
use App\Entity\Treatment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private EntityManagerInterface $em) {}

    public function index(): Response
    {
        $today     = new \DateTimeImmutable('today');
        $tomorrow  = new \DateTimeImmutable('tomorrow');
        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $monthEnd   = new \DateTimeImmutable('last day of this month 23:59:59');

        $users = $this->em->getRepository(User::class)->count([]);
        $farms = $this->em->getRepository(Farm::class)->count([]);

        $predictionsToday = $this->em->getRepository(Prediction::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt >= :start')
            ->andWhere('p.createdAt < :end')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        $activeAlerts = $this->em->getRepository(Alert::class)->count(['isRead' => false]);

        $treatmentsMonth = $this->em->getRepository(Treatment::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.date >= :start')
            ->andWhere('t.date <= :end')
            ->setParameter('start', $monthStart)
            ->setParameter('end', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'users'             => $users,
            'farms'             => $farms,
            'predictionsToday'  => $predictionsToday,
            'activeAlerts'      => $activeAlerts,
            'treatmentsMonth'   => $treatmentsMonth,
        ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addAssetMapperEntry('admin');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('AgriTech Cammarata — Admin')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('messages')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Utenti & Aziende');
        yield MenuItem::linkToCrud('Utenti', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Aziende', 'fa fa-building', Farm::class);
        yield MenuItem::linkToCrud('Parcelle', 'fa fa-map', Parcel::class);
        yield MenuItem::section('Dati Agronomici');
        yield MenuItem::linkToCrud('Previsioni', 'fa fa-chart-line', Prediction::class);
        yield MenuItem::linkToCrud('Alert', 'fa fa-bell', Alert::class);
        yield MenuItem::linkToCrud('Trattamenti', 'fa fa-flask', Treatment::class);
        yield MenuItem::linkToCrud('Storico Stagionale', 'fa fa-calendar', FarmSeasonRecord::class);
        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Torna all\'app', 'fa fa-arrow-left', '/dashboard');
    }
}