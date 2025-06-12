<?php

namespace App\Repository;

use App\Entity\HomePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomePeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomePeriod::class);
    }

    public function findActivePeriods(\DateTime $date = null): array
    {
        if (!$date) {
            $date = new \DateTime();
        }

        return $this->createQueryBuilder('hp')
            ->where('hp.dateDebut <= :date')
            ->andWhere('hp.dateFin >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function findOverlappingPeriods(int $homeId, \DateTime $dateDebut, \DateTime $dateFin, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('hp')
            ->where('hp.home = :homeId')
            ->andWhere('(hp.dateDebut <= :dateFin AND hp.dateFin >= :dateDebut)')
            ->setParameter('homeId', $homeId)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        if ($excludeId) {
            $qb->andWhere('hp.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByHome(int $homeId): array
    {
        return $this->findBy(['home' => $homeId], ['dateDebut' => 'ASC']);
    }

    public function findOneById(int $id): ?HomePeriod
    {
        return $this->createQueryBuilder('hp')
            ->addSelect('h')
            ->leftJoin('hp.home', 'h')
            ->where('hp.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByFilters(array $filters = []): array
    {
        $query = $this->createQueryBuilder('hp')
            ->innerJoin('hp.home', 'h')
            ->select('hp, h');
        
        
        if (!empty($filters['region'])) {
            $query->andWhere('h.region = :region')
            ->setParameter('region', $filters['region']);
        }
        if (!empty($filters['residence'])) {
            $query->andWhere('h.residence = :residence')
            ->setParameter('residence', $filters['residence']);
        }
        if (!empty($filters['nombreChambres'])) {
            $query->andWhere('h.nombreChambres = :nombreChambres')
            ->setParameter('nombreChambres', $filters['nombreChambres']);
        }
        
        
        return $query->orderBy('hp.dateDebut', 'ASC')->getQuery()->getResult();
        
    }

    
    
}