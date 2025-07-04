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
use Psr\Log\LoggerInterface;

class ReservationRepository extends ServiceEntityRepository
{
    private LoggerInterface $logger;
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Reservation::class);
        $this->logger = $logger;
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

    public function findActiveReservationByHomePeriod(HomePeriod $homePeriod): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.homePeriod', 'hp')
            ->where('hp = :homePeriod')
            ->andWhere('r.isSelected = :selected')
            ->setParameter('homePeriod', $homePeriod)
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

    
    private function createFilteredQueryBuilder(array $sort): QueryBuilder
    {
        $query = $this->createQueryBuilder('r')
        ->leftJoin('r.homePeriod', 'hp')->addSelect('hp')
        ->leftJoin('hp.home', 'h')->addSelect('h')
        ->leftJoin('r.user', 'u')->addSelect('u');

        // Always order by isConfirmed DESC, then isSelected DESC, unless overridden
        if (isset($sort['field']) && isset($sort['direction']) && $sort['field'] === 'statut') {
            $query->addOrderBy('r.isConfirmed', strtoupper($sort['direction']))
                  ->addOrderBy('r.isSelected', strtoupper($sort['direction']));
            $this->logger->info('Sorting by statut with direction: ' . strtoupper($sort['direction']));
        } else {
            $query->addOrderBy('h.nom', 'ASC')
                ->addOrderBy('hp.dateDebut', 'ASC')
                ->addOrderBy('r.isConfirmed', 'DESC')
                ->addOrderBy('r.isSelected', 'DESC')
                ->addOrderBy('u.lastYear', 'ASC');
        }
        return $query;
        
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
    
    public function findAllOrdered(array $sort)
    {
        return $this->createFilteredQueryBuilder($sort)
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters = [], array $sort = []): array
    {
        $query = $this->createFilteredQueryBuilder($sort);
        $this->applyFilters($query, $filters);
        return $query->getQuery()->getResult();
    }

    public function findPaginatedByFilters(array $filters = [], array $sort = [], int $page=1, int $pageSize=10): Paginator
    {
        $queryBuilder = $this->createFilteredQueryBuilder($sort);
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