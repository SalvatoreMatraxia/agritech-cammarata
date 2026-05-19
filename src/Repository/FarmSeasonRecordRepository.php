<?php

namespace App\Repository;

use App\Entity\FarmSeasonRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FarmSeasonRecord>
 */
class FarmSeasonRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FarmSeasonRecord::class);
    }
}