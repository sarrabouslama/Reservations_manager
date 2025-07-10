<?php

namespace App\Repository;

use App\Entity\PiscineReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<PiscineReservation>
 *
 * @method PiscineReservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method PiscineReservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method PiscineReservation[]    findAll()
 * @method PiscineReservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PiscineReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PiscineReservation::class);
    }

    public function findByFilters(array $filters, ?int $page, ?int $pageSize)
    {
        $query = $this->createQueryBuilder('p')
            ->select('p', 'pi')
            ->leftJoin('p.piscine', 'pi');

        if (!empty($filters['region'])) {
            $query->andWhere('pi.region = :region')
                ->setParameter('region', $filters['region']);
        }

        if (!empty($filters['hotel'])) {
            $query->andWhere('pi.hotel = :hotel')
                ->setParameter('hotel', $filters['hotel']);
        }

        if ($page !== null && $pageSize !== null) {
            $query->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize)
                ->getQuery();
            return new Paginator($query,true);
        }
        else{
            return $query->getQuery()
                ->getResult();
        }
    }
}
