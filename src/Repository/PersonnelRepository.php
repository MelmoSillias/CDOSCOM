<?php

namespace App\Repository;

use App\Entity\Personnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Personnel>
 */
class PersonnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnel::class);
    }

    public function findOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
