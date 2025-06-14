<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findRandomUsers(int $count): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->orderBy('RAND()')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    public function findAllMatriculesIndexedById(): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u.id, u.matricule')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($results as $row) {
            $map[$row['matricule']] = $row['id'];
        }
        return $map;
    }
   

    
} 