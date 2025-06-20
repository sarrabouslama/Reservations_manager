<?php

namespace App\Repository;

use App\Entity\HomeImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomeImage>
 *
 * @method HomeImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method HomeImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method HomeImage[]    findAll()
 * @method HomeImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HomeImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeImage::class);
    }

//    /**
//     * @return HomeImage[] Returns an array of HomeImage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HomeImage
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
