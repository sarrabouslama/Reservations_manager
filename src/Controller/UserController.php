<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


#[IsGranted('ROLE_User','ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/user/{id}', name: 'app_user_profile')]
    public function index(int $id,UserRepository $userRepository, ReservationRepository $reservationRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $user = $userRepository->find($id);
            if (!$user) {
                throw $this->createNotFoundException('User not found');
            }
            $admin = true;
        
        } elseif ($this->isGranted('ROLE_USER')) {
            $user = $this->getUser();
            if (!$user || $user->getId() !== $id) {
                throw $this->createAccessDeniedException('You do not have acces to this page.');
            }
        }
        else {
            throw $this->createAccessDeniedException('You must be logged in to access this page.');
        }
        return $this->render('user/index.html.twig', [
            'user' => $user,
            'admin' => isset($admin) ? $admin : false,
        ]);
    }

    
    #[Route('/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('adhérent non trouvé');
        }
        // Check if the user is allowed to edit
        if (!$this->isGranted('ROLE_ADMIN') && $user->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet adhérent.');
        }

        

        $form = $this->createForm('App\Form\UserType', $user, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $user->setCin(str_pad($form->get('cin')->getData(), 8, '0', STR_PAD_LEFT));
                // Hash the password
                $user->setPassword($passwordHasher->hashPassword($user, $user->getCin()));
                // Check if the user already exists
                $existingUser = $userRepository->findOneBy(['matricule' => $user->getMatricule()]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->addFlash('danger', 'Un adhérent avec ce matricule existe déjà.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
                // Check if the user already exists by CIN
                $existingUserByCin = $userRepository->findOneBy(['cin' => $user->getCin()]);
                if ($existingUserByCin && $existingUserByCin->getId() !== $user->getId()) {
                    $this->addFlash('danger', 'Un adhérent avec ce CIN existe déjà.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }
            }
            $user->setSit($form->get('sit')->getData());
            // If the user has an image, handle the file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('users_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors du téléchargement de l\'image');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }

                // Delete old image if exists
                if ($user->getImage()) {
                    $oldImagePath = $this->getParameter('users_images_directory').'/'.$user->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $user->setImage($newFilename);
            }
            

            // Save the user
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'adhérent a été modifié avec succès');

            return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()]);
        }

        return $this->render('user/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

/* 
    #[Route('/user/edit-password/{id}', name: 'app_user_edit-password')]
    #[IsGranted('ROLE_ADMIN')]
    public function editPassword(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            if (!$newPassword || $newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Passwords do not match.');
                return $this->redirectToRoute('app_user_edit-password', ['id' => $user->getId()]);
            }
            else{
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_user_profile', ['id' => $user->getId()]);
            } 
        }
        return $this->render('user/edit-password.html.twig', [
            'user' => $user,
        ]);
    }
 */
    #[Route('/user/delete/{id}', name: 'user_delete_image', methods: ['POST'])]
    public function deleteImage(int $id,Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('adhérent non trouvé');
        }
        // Check if the user is allowed to edit
        if (!$this->isGranted('ROLE_ADMIN') && $user->getId() !== $this->getUser()->getId()) {
            $this->addFlash('danger','Vous n\'êtes pas autorisé à modifier cet adhérent.');
            return $this->redirectToRoute('app_home_main');
        }

        if ($request->isMethod('POST')) {
            // Delete the user's image
            $imagePath = $this->getParameter('users_images_directory').'/'.$user->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $user->setImage(null);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Image deleted successfully.');
        }

        return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
    }

    #[Route('/user/nom-by-matricule/{matricule}', name: 'user_nom_by_matricule')]
    public function nomByMatricule(string $matricule, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['matricule' => $matricule]);
        return new JsonResponse(['nom' => $user ? $user->getNom() : '']);
    }
    
}