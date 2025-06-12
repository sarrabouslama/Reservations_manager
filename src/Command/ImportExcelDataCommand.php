<?php

namespace App\Command;

use App\Entity\Home;
use App\Entity\HomePeriod;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class ImportExcelDataCommand extends Command
{
    protected static $defaultName = 'app:import-excel-data';
    protected static $defaultDescription = 'Import data from an Excel file into Home, HomePeriod, and User entities';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filePath', InputArgument::REQUIRED, 'The path to the Excel file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('filePath');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheets = $spreadsheet->getAllSheets();

            foreach ($sheets as $index => $sheet) {
                $sheetName = $sheet->getTitle();
                $io->section("Processing sheet: $sheetName (Sheet #$index)");

                switch (strtolower($sheetName)) {
                    case 'feuil3':
                        $this->processResidencesSheet($sheet, $io);
                        break;
                    case 'les périodes':
                        $this->processPeriodsSheet($sheet, $io);
                        break;
                    case 'bénificiaire 2024':
                        $this->processBeneficiariesSheet($sheet, $io);
                        break;
                    default:
                        $io->warning("Unknown sheet: $sheetName");
                }
            }

            $this->entityManager->flush();
            $io->success('Excel import completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Error during import: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function processResidencesSheet($sheet, SymfonyStyle $io): void
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headers = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, false)[0];

        // Extract nombreChambres from headers (S+1, S+2, S+3)
        $nombreChambresOptions = [];
        foreach ($headers as $index => $header) {
            if (preg_match('/S\+(\d+)/', $header, $matches)) {
                $nombreChambresOptions[] = (int)$matches[1];
            }
        }
        $maxNombreChambres = max($nombreChambresOptions);

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false)[0];
            if (empty(array_filter($data))) continue; // Skip empty rows
            
            for ($i=0; $i< count($nombreChambresOptions); $i++) {
                $residence=$data[0] ?? 'Unknown';
                $region=$data[1] ?? 'Unknown';
                $nb_chambres = $nombreChambresOptions[$i];
                $maxUsers = (int)($data[$i + 2] ?? 0);
                $home = $this->entityManager->getRepository(Home::class)->findOneBy([
                    'residence' => $residence,
                    'region' => $region,
                    'nombreChambres' => $nb_chambres,
                ]);
                if ($maxUsers <= 0) {
                    $this->entityManager->remove($home); // Remove invalid home
                    continue; // Skip if max users is not valid
                }
                if (!$home) {
                    $home = new Home();
                }
                $home->setResidence($residence);
                $home->setRegion($region);
                $home->setNombreChambres($nb_chambres); 
                $home->setMaxUsers($maxUsers);
                $home->setNom($residence . ' - S+' . $nb_chambres);
                $home->setPrix(0.0); // Default price, adjust as needed
                $home->setDistancePlage(0.0); // Default, adjust if data exists
                $this->entityManager->persist($home);
            }

            $io->text("Imported Home: $residence in $region");
        }

        $this->entityManager->flush();
    }

    

    private function processBeneficiariesSheet($sheet, SymfonyStyle $io): void
    {
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:B{$row}", null, true, false)[0];
            if (empty(array_filter($data))) continue;

            $matricule = $data[0] ?? null;
            if ($matricule && is_numeric($matricule)) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['matricule' => $matricule]);
                if (!$user) {
                    continue;
                }
                $user->setLastYear(true); // Mark as beneficiary for 2024
                $this->entityManager->persist($user);
                
                $io->text("Imported/Updated User with Matricule: $matricule");
            }
        }
    }
}