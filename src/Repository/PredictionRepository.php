<?php

namespace App\Repository;

use App\Entity\Farm;
use App\Entity\Prediction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prediction>
 */
class PredictionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prediction::class);
    }

    public function findLatestByType(Farm $farm, string $type): ?Prediction
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.farm = :farm')
            ->andWhere('p.type = :type')
            ->setParameter('farm', $farm)
            ->setParameter('type', $type)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}