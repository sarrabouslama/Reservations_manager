<?php

namespace App\Repository;

use App\Entity\UserTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTicket>
 *
 * @method UserTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserTicket[]    findAll()
 * @method UserTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTicket::class);
    }

//    /**
//     * @return UserTicket[] Returns an array of UserTicket objects
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

//    public function findOneBySomeField($value): ?UserTicket
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
