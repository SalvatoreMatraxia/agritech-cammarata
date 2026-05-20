<?php

namespace App\Repository;

use App\Entity\Farm;
use App\Entity\Treatment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Treatment>
 */
class TreatmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Treatment::class);
    }

    /** @return Treatment[] */
    public function findByFarm(Farm $farm, int $page = 1, int $limit = 20, ?int $parcelId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->setParameter('farm', $farm)
            ->orderBy('t.date', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($parcelId) {
            $qb->andWhere('p.id = :parcelId')->setParameter('parcelId', $parcelId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFarm(Farm $farm, ?int $parcelId = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->setParameter('farm', $farm);

        if ($parcelId) {
            $qb->andWhere('p.id = :parcelId')->setParameter('parcelId', $parcelId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return Treatment[] */
    public function findRecentByFarm(Farm $farm, int $daysBack = 30): array
    {
        $since = new \DateTimeImmutable("-{$daysBack} days");

        return $this->createQueryBuilder('t')
            ->join('t.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->andWhere('t.date >= :since')
            ->setParameter('farm', $farm)
            ->setParameter('since', $since)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Treatment[] */
    public function findAllByFarmOrdered(Farm $farm): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->setParameter('farm', $farm)
            ->orderBy('t.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}