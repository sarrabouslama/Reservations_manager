<?php

namespace App\Repository;

use App\Entity\ResponsableTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResponsableTicket>
 *
 * @method ResponsableTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResponsableTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method ResponsableTicket[]    findAll()
 * @method ResponsableTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResponsableTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResponsableTicket::class);
    }

//    /**
//     * @return ResponsableTicket[] Returns an array of ResponsableTicket objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ResponsableTicket
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
