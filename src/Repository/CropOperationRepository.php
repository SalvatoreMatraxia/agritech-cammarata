<?php

namespace App\Repository;

use App\Entity\CropOperation;
use App\Entity\Farm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CropOperation>
 */
class CropOperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CropOperation::class);
    }

    /** @return CropOperation[] */
    public function findByFarm(Farm $farm, int $page = 1, int $limit = 20, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->setParameter('farm', $farm)
            ->orderBy('o.date', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($type) {
            $qb->andWhere('o.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFarm(Farm $farm, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.parcel', 'p')
            ->andWhere('p.farm = :farm')
            ->setParameter('farm', $farm);

        if ($type) {
            $qb->andWhere('o.type = :type')->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}