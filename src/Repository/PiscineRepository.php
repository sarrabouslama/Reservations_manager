<?php

namespace App\Repository;

use App\Entity\Piscine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Piscine>
 *
 * @method Piscine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Piscine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Piscine[]    findAll()
 * @method Piscine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PiscineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Piscine::class);
    }

    public function findAllRegions(array $filters): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('DISTINCT p.region');

        if (!empty($filters['hotel'])) {
            $query->andWhere('p.hotel = :hotel')
                ->setParameter('hotel', $filters['hotel']);
        }

        return $query->getQuery()
            ->getResult();
    }

    public function findAllHotels(array $filters): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('DISTINCT p.hotel');

        if (!empty($filters['region'])) {
            $query->andWhere('p.region = :region')
                ->setParameter('region', $filters['region']);
        }

        return $query->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters, ?int $page, ?int $pageSize)
    {
        $query = $this->createQueryBuilder('p');

        if (!empty($filters['region'])) {
            $query->andWhere('p.region = :region')
                ->setParameter('region', $filters['region']);
        }

        if (!empty($filters['hotel'])) {
            $query->andWhere('p.hotel = :hotel')
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
