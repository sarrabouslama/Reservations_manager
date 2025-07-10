<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NotificationRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_USER','ROLE_ADMIN', 'ROLE_SEMIADMIN')]
class NotificationController extends AbstractController
{
    #[Route('/notification', name: 'app_notification')]

    public function notifications(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to access this page.');
        }
        $criteria = $this->isGranted('ROLE_ADMIN') ? ['type' => 'admin'] : ['type' => 'user', 'user' => $user];
        $notifications = $notificationRepository->findBy($criteria, ['createdAt' => 'DESC']);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notification/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    public function markRead(int $id, EntityManagerInterface $em, NotificationRepository $notificationRepository): Response
    {
        $notification = $notificationRepository->find($id);
        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }
        if ($notification->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have access to this notification.');
        }
        $notification->setIsRead(true);
        $em->flush();
        return $this->redirectToRoute('app_notification');
    }

    // Mark as unread
    #[Route('/notification/{id}/unread', name: 'notification_mark_unread', methods: ['POST'])]
    public function markUnread(int $id, EntityManagerInterface $em, NotificationRepository $notificationRepository): Response
    {
        $notification = $notificationRepository->find($id);
        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }
        if ($notification->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have access to this notification.');
        }
        $notification->setIsRead(false);
        $em->flush();
        return $this->redirectToRoute('app_notification');
    }
}
