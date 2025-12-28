<?php

namespace App\Controller\Admin;

use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages', name: 'admin_messages')]
class MessageController extends AbstractController
{
    public function __invoke(MessageRepository $messageRepository): Response
    {
        $totalMessages = $messageRepository->count([]);
        $pendingMessages = $messageRepository->count(['status' => 'unread']);
        $readMessages = $messageRepository->count(['status' => 'read']);
        $respondedMessages = $messageRepository->count(['status' => 'responded']);

        return $this->render('admin/messages/index.html.twig', [
            'totalMessages' => $totalMessages,
            'unreadMessages' => $pendingMessages,
            'readMessages' => $readMessages,
            'respondedMessages' => $respondedMessages,
            'active_page' => 'messages',
        ]);
    }
}