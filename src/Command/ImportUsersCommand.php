<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImportUsersCommand extends Command
{
    protected static $defaultName = 'app:import-users';
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'The path to the .xls file to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $output->writeln('<error>File not found: ' . $filePath . '</error>');
            return Command::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            array_shift($rows); // Skip header row
            $totalRows = count($rows);
            $output->writeln('Total rows to process: ' . $totalRows);

            $batchSize = 100;
            $processedRows = 0;
            foreach ($rows as $index => $row) {
                if (empty($row[0])) {
                    $output->writeln('<error>Invalid or missing Matricule in row ' . ($index + 2) . ': ' . json_encode($row) . '</error>');
                    continue;
                }

                $user = $this->entityManager->getRepository(User::class)->findOneBy(['matricule' => $row[0]]);
                if (!$user) {
                    $user = new User();
                }
                $user->setMatricule($row[0]); // Matricule
                $user->setNom($row[1] ?? ''); // Nom
                $user->setCin(str_pad($row[2], 8, '0', STR_PAD_LEFT)); // CIN
                $user->setActif($row[3] === 'true' || $row[3] === 1 || $row[3] === '1' || $row[3] === 'Oui'); // Actif
                if (!empty($row[4])) {
                    $user->setTel($row[4]); // Téléphone
                }
                $user->setSit($row[6] ?? ''); // Situation
                $user->setNbEnfants((int)($row[7] ?? 0)); // Nombre d'enfants
                $user->setEmploi($row[8] ?? ''); // Emploi
                $user->setMatriculeCnss($row[9] ?? ''); // Matricule CNSS
                $user->setDirection($row[10] ?? ''); // Direction
                //$user->setEmail($row[11] ?? null); // Email
                //$user->setLastYear($row[12] === 'true' || $row[12] === 1 || $row[12] === '1' || $row[12] === 'Oui');
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getCin())); // Hash CIN as password
                $user->setRoles(['ROLE_USER']); // Default role
                $this->entityManager->persist($user);

                $processedRows++;
                if ($processedRows % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $output->writeln('Processed ' . $processedRows . ' rows...');
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
            $output->writeln('<info>Import completed. Total rows processed: ' . $processedRows . ' out of ' . $totalRows . '</info>');
            if ($processedRows < $totalRows) {
                $output->writeln('<warning>Warning: Not all rows were processed. Check logs for skipped rows.</warning>');
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            file_put_contents('import_errors.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            return Command::FAILURE;
        }
    }
}