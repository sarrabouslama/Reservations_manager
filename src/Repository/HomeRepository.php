<?php

namespace App\Repository;

use App\Entity\Home;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class HomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Home::class);
    }
    
    public function findByFilters(array $filters = []): array
    {
        $query = $this->createQueryBuilder('h');
        
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->andWhere("h.{$key} = :{$key}")
                    ->setParameter($key, $value );
            }
        }
        
        return $query->getQuery()
            ->getResult();
    }

    public function countReservationsByHome(Home $home): int
    {
        return $this->createQueryBuilder('h')
            ->select('COUNT(r.id)')
            ->leftJoin('h.reservations', 'r')
            ->where('h.id = :homeId')
            ->setParameter('homeId', $home->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllResidences(array $filters = []): array
    {
        $query =  $this->createQueryBuilder('p')
            ->select('DISTINCT p.residence');
        if (!empty($filters['region'])) {
            $query->andWhere('p.region = :region')
                  ->setParameter('region', $filters['region'] );
        }

        if (!empty($filters['nombreChambres'])) {
            $query->andWhere('p.nombreChambres = :nombreChambres')
                  ->setParameter('nombreChambres', $filters['nombreChambres']);
        }
        return ($query ->getQuery()
            ->getResult());
    }

    public function findAllRegions(array $filters = []): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('DISTINCT p.region');
        
        if (!empty($filters['residence'])) {
            $query->andWhere('p.residence = :residence')
                  ->setParameter('residence', $filters['residence'] );
        }

        if (!empty($filters['nombreChambres'])) {
            $query->andWhere('p.nombreChambres = :nombreChambres')
                  ->setParameter('nombreChambres', $filters['nombreChambres']);
        }
        return ($query->getQuery()
            ->getResult());
    }

    public function findAllNbChambres(array $filters = []): array
    {
        $query = $this->createQueryBuilder('p')
        ->select('DISTINCT p.nombreChambres AS nb')
        ->orderBy('nb', 'ASC');
        if (!empty($filters['region'])) {
            $query->andWhere('p.region = :region')
                  ->setParameter('region', $filters['region'] );
        }
        if (!empty($filters['residence'])) {
            $query->andWhere('p.residence = :residence')
                  ->setParameter('residence', $filters['residence'] );
        }
        $results = $query->getQuery()
        ->getScalarResult();

        // Flatten the result: from [['nb' => 2], ...] to [2, ...]
        return array_map(fn($row) => $row['nb'], $results);
    }
    
    public function findOneWithRelations(int $id): ?Home
    {
        $qb = $this->createQueryBuilder('h');
        
        // First get the home with its direct relationships
        $qb->select('h', 'hp', 'i')
           ->leftJoin('h.homePeriods', 'hp')
           ->leftJoin('h.images', 'i')
           ->where('h.id = :id')
           ->setParameter('id', $id);

        $home = $qb->getQuery()->getOneOrNullResult();

        if ($home) {
            // Now load the reservations separately
            $this->getEntityManager()->createQueryBuilder()
                ->select('r', 'hp')
                ->from('App\Entity\Reservation', 'r')
                ->leftJoin('r.homePeriod', 'hp')
                ->where('hp.home = :home')
                ->setParameter('home', $home)
                ->getQuery()
                ->getResult();
        }

        return $home;
    }

} 