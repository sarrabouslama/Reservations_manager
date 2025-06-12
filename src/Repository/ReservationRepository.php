<?php

namespace App\Repository;

use App\Entity\Home;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\HomePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findActiveReservationByUser(User $user): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.isSelected = :confirme')
            ->andWhere('r.dateFin >= :now')
            ->setParameter('user', $user)
            ->setParameter('confirme', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveReservationByHome(Home $home): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.homePeriod', 'hp')
            ->where('hp.home = :home')
            ->andWhere('r.isSelected = :selected')
            ->setParameter('home', $home)
            ->setParameter('selected', true)
            ->getQuery()
            ->getResult();
    }



    public function deleteUserReservations(User $user): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findPendingReservations(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isSelected = :confirme')
            ->setParameter('confirme', false)
            ->orderBy('r.dateReservation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    private function createFilteredQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('r')
        ->leftJoin('r.homePeriod', 'hp')->addSelect('hp')
        ->leftJoin('hp.home', 'h')->addSelect('h')
        ->leftJoin('r.user', 'u')->addSelect('u')
        ->orderBy('h.nom', 'ASC')
        ->addOrderBy('hp.dateDebut', 'ASC')
        ->addOrderBy('r.isConfirmed', 'DESC')
        ->addOrderBy('r.isSelected', 'DESC')
        ->addOrderBy('u.lastYear', 'ASC');
    }
    
    private function applyFilters(QueryBuilder $query, array $filters): void
    {
        if (!empty($filters['region'])) {
            // Use '=' for exact matches, it's much faster than LIKE.
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
    }
    
    public function findAllOrdered()
    {
        return $this->createFilteredQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters = []): array
    {
        $query = $this->createFilteredQueryBuilder();
        $this->applyFilters($query, $filters);
        return $query->getQuery()->getResult();
    }

    public function findPaginatedByFilters(array $filters = [], int $page, int $pageSize): Paginator
    {
        $queryBuilder = $this->createFilteredQueryBuilder();
        $this->applyFilters($queryBuilder, $filters);

        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery();

        return new Paginator($query, true);
    }
    
    public function findReservationsForPeriodsByLastYearStatus(array $periods, bool $lastYearStatus): array
    {
        if (empty($periods)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u') 
            ->where('r.homePeriod IN (:periods)')
            ->andWhere('r.isSelected = :isSelected')
            ->andWhere('u.lastYear = :lastYearStatus')
            ->setParameter('periods', $periods)
            ->setParameter('isSelected', false)
            ->setParameter('lastYearStatus', $lastYearStatus)
            ->getQuery()
            ->getResult();
    }
} 