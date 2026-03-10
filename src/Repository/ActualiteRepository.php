<?php

namespace App\Repository;

use App\Entity\Actualite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actualite>
 */
class ActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actualite::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.delaiPublication <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestPublished(int $limit = 3): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.delaiPublication <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
