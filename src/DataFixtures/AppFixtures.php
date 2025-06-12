<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Home;
use App\Entity\HomePeriod;
use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setMatricule(0);
        $admin->setCin(0);
        $admin->setEmail('admin@example.com');
        $admin->setNom('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setMatricule($i);
            $user->setCin($i);
            $user->setEmail('user'.$i.'@example.com');
            $user->setNom('User'.$i);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'.$i));
            $manager->persist($user);
            $users[] = $user;
        }

        // Create Home
        $homes = [];
        $region = ['hammamet','sousse','monastir'];
        for ($i = 1; $i <= 3; $i++) {
            $home = new Home();
            $home->setResidence("Residence $i");
            $home->setRegion($region[$i - 1]);
            $home->setNombreChambres(rand(1, 5));
            $home->setNom();
            $home->setDistancePlage(rand(0, 5) / 10); 
            $home->setPrix(rand(500, 2000)); 
            $home->setDescription("Description de la maison $i.");
            $manager->persist($home);
            $homes[] = $home;
        }
        
        //Periods
        $periods = [];
        foreach ($homes as $home) {
            for ($j = 0; $j < 2; $j++) { 
                $dateDebut = (new \DateTime())->modify("+$j week")->modify('next Sunday');
                $dateFin = (clone $dateDebut)->modify('+6 days');
                $homePeriod = new HomePeriod();
                $homePeriod->setHome($home);
                $homePeriod->setDateDebut($dateDebut);
                $homePeriod->setDateFin($dateFin);
                $homePeriod->setMaxUsers(rand(1, 3));
                $manager->persist($homePeriod);
                $periods[] = $homePeriod;
            }
        }
        
        // Create Reservations
        $usedUsers = [];
        foreach ($periods as $idx => $period) {
            foreach ($users as $user) {
                if (!in_array($user, $usedUsers, true)) {
                    $reservation = new Reservation();
                    $reservation->setUser($user);
                    $reservation->setHomePeriod($period);
                    $reservation->setEstBloque(false);
                    $reservation->setIsSelected(false);
                    $reservation->setDateReservation(new \DateTime());
                    $manager->persist($reservation);
                    $usedUsers[] = $user;
                    break; // Only one reservation per user
                }
            }
        }


        $manager->flush();
    }
}