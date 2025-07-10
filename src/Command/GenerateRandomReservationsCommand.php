<?php

namespace App\Command;

use App\Entity\HomePeriod;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class GenerateRandomReservationsCommand extends Command
{
    protected static $defaultName = 'app:generate-random-reservations';
    protected static $defaultDescription = 'Generate random reservations';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of reservations to generate', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int)$input->getOption('count');

        $homePeriods = $this->entityManager->getRepository(HomePeriod::class)->findAll();
        $users = $this->entityManager->getRepository(User::class)->findAll();

        if (empty($homePeriods) || empty($users)) {
            $output->writeln('No home periods or users found in the database.');
            return Command::FAILURE;
        }

        shuffle($users);
        shuffle($homePeriods);

        for ($i = 0; $i < $count; $i++) {
            $reservation = new Reservation();
            $reservation->setHomePeriod($homePeriods[$i % count($homePeriods)]);
            $reservation->setUser($users[$i % count($users)]);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setEstBloque(false);
            $reservation->setIsSelected(false);
            $reservation->setDateSelection(null);
            $reservation->setIsConfirmed(false);

            $this->entityManager->persist($reservation);
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('%d random reservations generated.', $count));
        return Command::SUCCESS;
    }
}