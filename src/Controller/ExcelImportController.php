<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ExcelUploadType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[IsGranted('ROLE_ADMIN')]

class ExcelImportController extends AbstractController
{
    #[Route('/import-excel', name: 'import_excel')]
    public function import(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(ExcelUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('excelFile')->getData();

            try {
                // Load the Excel file
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                // Skip header row
                $header = array_shift($rows);

                foreach ($rows as $row) {
                    // Map Excel columns to entity fields (adjust indices as needed)
                    $user = new User();
                    $user->setMatricule($row[0]); // Matricule
                    $user->setNom($row[1]); // Nom
                    $user->setCin($row[2]); // CIN
                    $user->setActif($row[3] === 'true' || $row[3] === 1); // Actif
                    $user->setTel($row[4]); // Téléphone
                    $user->setSit($row[5]); // Situation
                    $user->setNbEnfants((int)$row[6]); // Nombre d'enfants
                    $user->setEmploi($row[7]); // Emploi
                    $user->setMatriculeCnss($row[8]); // Matricule CNSS
                    $user->setDirection($row[9]); // Direction
                    $user->setPassword($passwordHasher->hashPassword($user, $user->getCin())); // Password (ensure to hash it later)
                    
                    // Persist the entity
                    $entityManager->persist($user);
                }

                // Save to database
                $entityManager->flush();

                $this->addFlash('success', 'Data imported successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error importing data: ' . $e->getMessage());
            }

            return $this->redirectToRoute('import_excel');
        }

        return $this->render('excel_import/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}