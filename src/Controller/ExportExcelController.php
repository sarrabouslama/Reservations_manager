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
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);     

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item->getUser()->getMatricule());
            $sheet->setCellValue('B' . $row, $item->getUser()->getNom());
            $sheet->setCellValue('C' . $row, $item->getHomePeriod()->getHome()->getRegion());
            $sheet->setCellValue('D' . $row, $item->getHome()->getNom());
            $sheet->setCellValue('E' . $row, $item->getHomePeriod()->getDateDebut()->format('d-m-Y'));
            $sheet->setCellValue('F' . $row, $item->getHomePeriod()->getDateFin()->format('d-m-Y'));
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
        $data = $repository->findAll();
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
        $sheet->setCellValue('K1', '');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

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
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

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
}