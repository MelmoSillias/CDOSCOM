<?php

namespace App\Controller\Admin;

use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages', name: 'admin_messages')]
class MessageController extends AbstractController
{
    public function __invoke(MessageService $messageService): Response
    {
        $stats = $messageService->getStats();

        return $this->render('admin/messages/index.html.twig', [
            'totalMessages' => $stats['totalMessages'],
            'unreadMessages' => $stats['unreadMessages'],
            'readMessages' => $stats['readMessages'],
            'respondedMessages' => $stats['respondedMessages'],
            'active_page' => 'messages',
        ]);
    }
}