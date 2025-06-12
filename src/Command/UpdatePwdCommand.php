<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;


#[AsCommand(
    name: 'app:updatePwd',
    description: 'updates all passwords',
)]
class UpdatePwdCommand extends Command
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize = 100;
        $processedRows = 0;

        $output->writeln('Updating user passwords...');

        $users = $this->entityManager->getRepository(User::class)->findAll();
        if (empty($users)) {
            $io->warning('No users found in the database.');
            return Command::FAILURE;
        }
        foreach ($users as $user) {
            // Generate a new password (for demonstration, using a static password)
            $newPassword = $user->getCin(); // Replace with your logic to generate a secure password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Persist the changes
            $this->entityManager->persist($user);
            $processedRows++;
            if ($processedRows % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $output->writeln('Processed ' . $processedRows . ' rows...');
            }
        }
        // Flush the changes to the database
        $this->entityManager->flush();
        $io->success('All user passwords have been updated successfully.');


        return Command::SUCCESS;
    }
}
