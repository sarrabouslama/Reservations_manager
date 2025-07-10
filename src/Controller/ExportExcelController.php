<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Reservation;
use App\Entity\Home;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportExcelController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/export/excel/reservations', name: 'export_excel_reservations')]
    public function exportExcelReservations(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\Reservation');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule Adhérent');
        $sheet->setCellValue('B1', 'Nom Adhérent');
        $sheet->setCellValue('C1', 'Region');
        $sheet->setCellValue('D1', 'Maison');
        $sheet->setCellValue('E1', 'Date de début');
        $sheet->setCellValue('F1', 'Date de fin');
        $sheet->setCellValue('G1', 'Maison 2024');
        $sheet->setCellValue('H1', 'Statut de réservation');
        $sheet->setCellValue('I1', 'date de réservation');
        $sheet->setCellValue('K1', 'Téléphone');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getUser()->getNom());
            $sheet->setCellValue('C' . $row, $item->getHomePeriod()->getHome()->getRegion());
            $sheet->setCellValue('D' . $row, $item->getHome()->getNom());
            $dateDebut = $item->getHomePeriod()->getDateDebut();
            if ($dateDebut instanceof \DateTimeInterface) {
                $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateDebut);
                $sheet->setCellValue('E' . $row, $excelDateValue);
                $sheet->getStyle('E' . $row)
                    ->getNumberFormat()
                    ->setFormatCode('dd-mm-yyyy');
            } else {
                $sheet->setCellValue('E' . $row, '');
            }
            $dateFin = $item->getHomePeriod()->getDateFin();
            if ($dateFin instanceof \DateTimeInterface) {
                $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateFin);
                $sheet->setCellValue('F' . $row, $excelDateValue);
                $sheet->getStyle('F' . $row)
                    ->getNumberFormat()
                    ->setFormatCode('dd-mm-yyyy');
            } else {
                $sheet->setCellValue('F' . $row, '');
            }
            $sheet->setCellValue('G' . $row, $item->getUser()->isLastYear() ? 'Oui' : 'Non');
            $status = $item->isConfirmed() ? 'Confirmée' : ($item->isSelected() ? 'Réservée' : 'En attente');
            $sheet->setCellValue('H' . $row, $status);
            if ($status === 'Réservée') {
                $sheet->getStyle('H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFADD8E6'); 
            } elseif ($status === 'Confirmée') {
                $sheet->getStyle('H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF90EE90'); 
            }
            $dateReservation = $item->getDateReservation();
            if ($dateReservation instanceof \DateTimeInterface) {
                $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateReservation);
                $sheet->setCellValue('I' . $row, $excelDateValue);
                $sheet->getStyle('I' . $row)
                    ->getNumberFormat()
                    ->setFormatCode('dd-mm-yyyy');
            } else {
                $sheet->setCellValue('I' . $row, '');
            }
            $sheet->setCellValue('K' . $row, $item->getUser()->getTel());
            $row++;
        }

           $writer = new Xlsx($spreadsheet);

        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'reservations.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response->setContent($content);

        return $response;
    }

    #[Route('/export/excel/homes', name: 'export_excel_homes')]
    public function exportExcelHomes(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\Home');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Résidence');
        $sheet->setCellValue('B1', 'Région');
        $sheet->setCellValue('C1', 'S+');
        $sheet->setCellValue('D1', 'Prix');
        $sheet->setCellValue('E1', 'Description');
        $sheet->setCellValue('F1', 'Périodes');
        $sheet->setCellValue('G1', 'Nombre de Maisons');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $periods = $item->getHomePeriods();
            $periodCount = count($periods);
            $first = true;
            foreach ($periods as $period) {
                if ($first) {
                    $sheet->setCellValue('A' . $row, $item->getResidence());
                    $sheet->setCellValue('B' . $row, $item->getRegion());
                    $sheet->setCellValue('C' . $row, $item->getNombreChambres());
                    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->setCellValue('D' . $row, $item->getPrix() . ' DT');
                    $sheet->setCellValue('E' . $row, $item->getDescription() ?? null);
                    $first = false;
                }
                $sheet->setCellValue('F' . $row, $period->getDateDebut()->format('d-m-Y') . ' au ' . $period->getDateFin()->format('d-m-Y'));
                $sheet->setCellValue('G' . $row, $period->getMaxUsers() ?? 0);
                $row++;
            }
            // Merge cells for home info if there are multiple periods
            if ($periodCount > 1) {
                foreach (['A','B','C','D','E'] as $col) {
                    $sheet->mergeCells("{$col}" . ($row - $periodCount) . ":{$col}" . ($row - 1));
                }
            }
        }

        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'homes.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/available/homes', name: 'export_excel_available_homes')]
    public function exportExcelAvailableHomes(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\Home');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Résidence');
        $sheet->setCellValue('B1', 'Région');
        $sheet->setCellValue('C1', 'S+');
        $sheet->setCellValue('D1', 'Prix');
        $sheet->setCellValue('E1', 'Périodes');
        $sheet->setCellValue('F1', 'Nb disponible');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $periods = $item->getHomePeriods();
            $availablePeriods = [];
            foreach ($periods as $period) {
                $nbDispo = $period->getMaxUsers() - $period->getReservations()->count();
                if ($nbDispo > 0) {
                    $availablePeriods[] = [
                        'period' => $period,
                        'nbDispo' => $nbDispo
                    ];
                }
            }
            $periodCount = count($availablePeriods);
            if ($periodCount === 0) {
                continue; // No available periods for this home
            }
            $first = true;
            $startRow = $row;
            foreach ($availablePeriods as $ap) {
                $period = $ap['period'];
                $nbDispo = $ap['nbDispo'];
                if ($first) {
                    $sheet->setCellValue('A' . $row, $item->getResidence());
                    $sheet->setCellValue('B' . $row, $item->getRegion());
                    $sheet->setCellValue('C' . $row, $item->getNombreChambres());
                    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    $sheet->setCellValue('D' . $row, $item->getPrix() . ' DT');
                    $first = false;
                }
                $sheet->setCellValue('E' . $row, $period->getDateDebut()->format('d-m-Y') . ' au ' . $period->getDateFin()->format('d-m-Y'));
                $sheet->setCellValue('F' . $row, $nbDispo);
                $row++;
            }
            // Merge cells for home info if there are multiple available periods and the range is valid
            $endRow = $row - 1;
            if ($periodCount > 1 && $endRow > $startRow) {
                foreach (['A','B','C','D'] as $col) {
                    $sheet->mergeCells("{$col}{$startRow}:{$col}{$endRow}");
                }
            }
        }

        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'maisons_disponibles.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/users', name: 'export_excel_users')]
    public function exportExcelUsers(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\User');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule');
        $sheet->setCellValue('B1', 'Nom');
        $sheet->setCellValue('C1', 'CIN');
        $sheet->setCellValue('D1', 'Actif');
        $sheet->setCellValue('E1', 'Téléphone');
        $sheet->setCellValue('F1', 'Situation');
        $sheet->setCellValue('G1', 'Nombre d\'enfants');
        $sheet->setCellValue('H1', 'Emploi');
        $sheet->setCellValue('I1', 'Matricule CNSS');
        $sheet->setCellValue('J1', 'Direction');
        $sheet->setCellValue('K1', 'Email');
        $sheet->setCellValue('L1', 'Maison 2024');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);     

        $row = 2;
        foreach ($data as $item) {
            if (in_array('ROLE_ADMIN', $item->getRoles())) {
                continue; // Skip admin users
            }
            $sheet->setCellValue('A' . $row, $item->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getNom());
            $sheet->setCellValue('C' . $row, $item->getCin());
            $sheet->setCellValue('D' . $row, $item->isActif() ? 'Oui' : 'Non');
            if ($item->getTel()) {
                $sheet->setCellValue('E' . $row, $item->getTel());
            } else {
                $sheet->setCellValue('E' . $row, '');
            }
            
            $sheet->setCellValue('F' . $row, $item->getSit() ?? '');            
            $sheet->setCellValue('G' . $row, $item->getNbEnfants() ?? 0);
            $sheet->setCellValue('H' . $row, $item->getEmploi() ?? '');
            $sheet->setCellValue('I' . $row, $item->getMatriculeCnss() ?? '');
            $sheet->setCellValue('J' . $row, $item->getDirection() ?? '');
            if ($item->getEmail()) {
                $sheet->setCellValue('K' . $row, $item->getEmail());
            } else {
                $sheet->setCellValue('K' . $row, '');
            }
            $sheet->setCellValue('L' . $row, $item->isLastYear() ? 'Oui' : 'Non');
            $row++;
        }
        $writer = new Xlsx($spreadsheet);

        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'users.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response->setContent($content);

        return $response;
    }

    #[Route('/export/excel/payements', name: 'export_excel_payements')]
    public function exportExcelPayements(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\Payement');
        $query = $repository->createQueryBuilder('p')
            ->where('p.reservation IS NOT NULL')
            ->getQuery();
        $data = $query->getResult();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule Adhérent');
        $sheet->setCellValue('B1', 'Nom et Prenom');
        $sheet->setCellValue('C1', 'Aff');
        $sheet->setCellValue('D1', 'Montant');
        $sheet->setCellValue('E1', 'Nb mois');
        $sheet->setCellValue('F1', 'Mensuel');
        $sheet->setCellValue('G1', 'Mode echéance');
        $sheet->setCellValue('H1', 'Code Opposition');
        $sheet->setCellValue('I1', 'Date Début');
        $sheet->setCellValue('J1', 'Date Saisie');
        $sheet->setCellValue('K1', 'Téléphone');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getReservation()->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getReservation()->getUser()->getNom());
            $sheet->setCellValue('C' . $row, $item->getReservation()->getUser()->getEmploi());
            $reste = $item->getMontantGlobal() - $item->getAvance();
            $sheet->setCellValue('D' . $row, number_format($reste, 2, ',', ' ') . ' DT');
            $sheet->setCellValue('E' . $row, $item->getNbMois());
            $sheet->setCellValue('F' . $row, number_format($reste / $item->getNbMois(), 2, ',', ' ') . ' DT');
            $sheet->setCellValue('G' . $row, $item->getModeEcheance());
            $sheet->setCellValue('H' . $row, $item->getCodeOpposition());
            $sheet->setCellValue('I' . $row, $item->getDateDebut()->format('d-m-Y'));
            $sheet->setCellValue('J' . $row, $item->getDateSaisie()->format('d-m-Y'));
            $sheet->setCellValue('K' . $row, $item->getReservation()->getUser()->getTel());
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'oppositions.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/tickets', name: 'export_excel_tickets')]
    public function exportExcelTickets(): Response
    {
        $repository = $this->entityManager->getRepository('App\Entity\UserTicket');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule Adhérent');
        $sheet->setCellValue('B1', 'Nom et Prenom');
        $sheet->setCellValue('C1', 'Localisation');
        $sheet->setCellValue('D1', 'Prix Unitaire');
        $sheet->setCellValue('E1', 'quantité');
        $sheet->setCellValue('F1', 'Total');
        $sheet->setCellValue('G1', 'Avance');
        $sheet->setCellValue('H1', 'Reliquat');
        $sheet->setCellValue('I1', 'Nb mois');
        $sheet->setCellValue('J1', 'Mode echeance');
        $sheet->setCellValue('K1', 'Code Opposition');
        $sheet->setCellValue('L1', 'Date début');
        $sheet->setCellValue('M1', 'Date Saisie');
        $sheet->setCellValue('N1', 'Téléphone');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getUser()->getNom());
            $sheet->setCellValue('C' . $row, $item->getResponsable()->getTicket()->getLocalisation());
            $sheet->setCellValue('D' . $row, number_format($item->getPrixUnitaire(), 2, ',', ' ') . ' DT');
            $sheet->setCellValue('E' . $row, $item->getNombre());
            $sheet->setCellValue('F' . $row, number_format($item->getTotal(), 2, ',', ' ') . ' DT');
            $sheet->setCellValue('G' . $row, number_format($item->getAvance(), 2, ',', ' ') . ' DT');
            $reste = $item->getTotal() - $item->getAvance();
            $sheet->setCellValue('H' . $row, number_format($reste, 2, ',', ' ') . ' DT');
            $sheet->setCellValue('I' . $row, $item->getNbMois());
            $sheet->setCellValue('J' . $row, $item->getModeEcheance());
            $sheet->setCellValue('K' . $row, $item->getCodeOpposition());
            $sheet->setCellValue('L' . $row, $item->getDateDebut()->format('d-m-Y'));
            $sheet->setCellValue('M' . $row, $item->getDateSaisie()->format('d-m-Y'));
            $sheet->setCellValue('N' . $row, $item->getUser()->getTel());
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'CarthageLand.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/piscines', name: 'export_excel_piscines')]
    public function exportExcelPiscines(): Response {
        $repository = $this->entityManager->getRepository('App\Entity\Piscine');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Hotel');
        $sheet->setCellValue('B1', 'Région');
        $sheet->setCellValue('C1', 'Prix initial');
        $sheet->setCellValue('D1', 'consommation');
        $sheet->setCellValue('E1', 'Réduction');
        $sheet->setCellValue('F1', 'Prix final');
        $sheet->setCellValue('G1', 'nombre de personnes');
        $sheet->setCellValue('H1', 'nombre d\'enfants');
        $sheet->setCellValue('I1', 'nombre d\'adultes');
        $sheet->setCellValue('J1', 'description');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getHotel());
            $sheet->setCellValue('B' . $row, $item->getRegion());
            $sheet->setCellValue('C' . $row, number_format($item->getPrixInitial(),0, ',', ' ') . ' DT');
            $sheet->setCellValue('D' . $row, number_format($item->getConsommation(), 0, ',', ' ') . ' DT'); 
            $sheet->setCellValue('E' . $row, number_format($item->getAmicale(), 0, ',', ' ') . ' DT');
            $sheet->setCellValue('F' . $row, number_format($item->getPrixFinal(), 0, ',', ' ') . ' DT');
            $sheet->setCellValue('G' . $row, $item->getNbPersonnes());
            $sheet->setCellValue('H' . $row, $item->getNbEnfants());
            $sheet->setCellValue('I' . $row, $item->getNbAdultes());
            $sheet->setCellValue('J' . $row, $item->getDescription());
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'piscine.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/piscine_reservations', name: 'export_excel_piscine_reservations')]
    public function exportExcelPiscineReservations(): Response {
        $repository = $this->entityManager->getRepository('App\Entity\PiscineReservation');
        $data = $repository->findAll();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule Adhérent');
        $sheet->setCellValue('B1', 'Nom et Prenom');
        $sheet->setCellValue('C1', 'Hotel');
        $sheet->setCellValue('D1', 'Région');
        $sheet->setCellValue('E1', 'Prix final');
        $sheet->setCellValue('F1', 'Status');
        $sheet->setCellValue('G1', 'Téléphone');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getUser()->getNom());
            $sheet->setCellValue('C' . $row, $item->getPiscine()->getHotel());
            $sheet->setCellValue('D' . $row, $item->getPiscine()->getRegion());
            $sheet->setCellValue('E' . $row, number_format($item->getPiscine()->getPrixFinal(), 0, ',', ' ') . ' DT');
            $status = $item->isConfirmed() ? 'Confirmée' : ($item->isSelected() ? 'Réservée' : 'En attente');
            $sheet->setCellValue('F' . $row, $status);
            if ($status === 'Réservée') {
                $sheet->getStyle('F' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFADD8E6'); 
            } elseif ($status === 'Confirmée') {
                $sheet->getStyle('F' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FF90EE90'); 
            }
            $sheet->setCellValue('G' . $row, $item->getUser()->getTel());
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'piscine_reservations.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

    #[Route('/export/excel/payements_piscine', name: 'export_excel_payements_piscine')]
    public function exportExcelPayementsPiscine(): Response {
        $repository = $this->entityManager->getRepository('App\Entity\Payement');
        $query = $repository->createQueryBuilder('p')
            ->where('p.piscineReservation IS NOT NULL')
            ->getQuery();
        $data = $query->getResult();
        if (!$data) {
            return new Response('Aucune donnée à exporter.', 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Matricule Adhérent');
        $sheet->setCellValue('B1', 'Nom et Prenom');
        $sheet->setCellValue('C1', 'Montant');
        $sheet->setCellValue('D1', 'Aff');
        $sheet->setCellValue('E1', 'Nb mois');
        $sheet->setCellValue('F1', 'Mensuel');
        $sheet->setCellValue('G1', 'Mode echéance');
        $sheet->setCellValue('H1', 'Code Opposition');
        $sheet->setCellValue('I1', 'Date Début');
        $sheet->setCellValue('J1', 'Date Saisie');
        $sheet->setCellValue('K1', 'Téléphone');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getReservation()->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getReservation()->getUser()->getNom());
            $reste = $item->getMontantGlobal() - $item->getAvance();
            $sheet->setCellValue('C' . $row, number_format($reste, 0, ',', ' ') . ' DT');
            $sheet->setCellValue('D' . $row, number_format($reste, 0, ',', ' ') . ' DT');
            $sheet->setCellValue('E' . $row, $item->getNbMois());
            $sheet->setCellValue('F' . $row, number_format($reste / $item->getNbMois(), 0, ',', ' ') . ' DT');
            $sheet->setCellValue('G' . $row, $item->getModeEcheance());
            $sheet->setCellValue('H' . $row, $item->getCodeOpposition());
            $sheet->setCellValue('I' . $row, $item->getDateDebut()->format('d-m-Y'));
            $sheet->setCellValue('J' . $row, $item->getDateSaisie()->format('d-m-Y'));
            $sheet->setCellValue('K' . $row, $item->getReservation()->getUser()->getTel());
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'oppositions_piscine.xlsx'
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

}