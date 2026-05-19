<?php

namespace App\Repository;

use App\Entity\PredictionAccuracy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PredictionAccuracy>
 */
class PredictionAccuracyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PredictionAccuracy::class);
    }
}