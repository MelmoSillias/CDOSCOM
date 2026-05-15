<?php

namespace App\Controller\Api;

use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    private MessageService $messageService;
    private ValidatorInterface $validator;

    public function __construct(MessageService $messageService, ValidatorInterface $validator)
    {
        $this->messageService = $messageService;
        $this->validator = $validator;
    }

    #[Route('', name: 'api_message_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide.'], 400);
        }

        if (!isset($data['message']) || empty(trim($data['message']))) {
            return $this->json(['error' => 'Le message ne peut pas être vide.'], 400);
        }

        if (!isset($data['phone']) || mb_strlen(trim((string) $data['phone'])) < 6) {
            return $this->json(['error' => 'Le numero de telephone est obligatoire et doit contenir au moins 6 caracteres.'], 400);
        }

        try {
            $message = $this->messageService->createMessage($data);

            $mailStatus = $message->getStatutEnvoiMail() ?? 'failed';
            $mailDelivered = $mailStatus === 'sent';

            return $this->json([
                'message' => $mailDelivered
                    ? 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.'
                    : 'Votre message a été enregistré, mais la notification e-mail a échoué. Veuillez contacter l\'administrateur.',
                'id' => $message->getId(),
                'mailStatus' => $mailStatus,
                'mailDelivered' => $mailDelivered,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.'], 500);
        }
    }

    #[Route('/count', name: 'api_message_count', methods: ['GET'])]
    public function count(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        if ($status === 'pending') {
            return $this->json(['count' => $this->messageService->countMessages('unread')]);
        }

        $count = $this->messageService->countMessages($status);

        return $this->json(['count' => $count]);
    }

    #[Route('/stats', name: 'api_message_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return $this->json($this->messageService->getStats());
    }

    #[Route('', name: 'api_message_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $dateStart = $request->query->get('date_start');
        $dateEnd = $request->query->get('date_end');

        $messages = $this->messageService->getFilteredMessages($type, $status, $dateStart, $dateEnd);
        $data = array_map(function ($message) {
            return [
                'id' => $message->getId(),
                'firstName' => $message->getFirstName(),
                'lastName' => $message->getLastName(),
                'email' => $message->getEmail(),
                'phone' => $message->getPhone(),
                'subject' => $message->getSubject(),
                'appointmentDate' => $message->getAppointmentDate()?->format('Y-m-d'),
                'content' => $message->getContent(),
                'type' => $message->getType(),
                'status' => $message->getStatus(),
                'statutEnvoiMail' => $message->getStatutEnvoiMail(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $messages);

        return $this->json(['data' => $data]);
    }

    #[Route('/{id}', name: 'api_message_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $message = $this->messageService->getMessageById($id);

        if (!$message) {
            return $this->json(['error' => 'Message not found'], 404);
        }

        return $this->json([
            'id' => $message->getId(),
            'firstName' => $message->getFirstName(),
            'lastName' => $message->getLastName(),
            'email' => $message->getEmail(),
            'phone' => $message->getPhone(),
            'subject' => $message->getSubject(),
            'appointmentDate' => $message->getAppointmentDate()?->format('Y-m-d'),
            'content' => $message->getContent(),
            'type' => $message->getType(),
            'status' => $message->getStatus(),
            'statutEnvoiMail' => $message->getStatutEnvoiMail(),
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}', name: 'api_message_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        try {
            $message = $this->messageService->updateMessage($id, $data);
            return $this->json([
                'message' => 'Message mis à jour avec succès.',
                'id' => $message->getId(),
                'status' => $message->getStatus()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'api_message_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->messageService->deleteMessage($id);
            return $this->json(['message' => 'Message supprimé avec succès.']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/retry-mail', name: 'api_message_retry_mail', methods: ['POST'])]
    public function retryMail(int $id): JsonResponse
    {
        try {
            $message = $this->messageService->retryMailDelivery($id);

            return $this->json([
                'message' => 'Tentative d\'envoi relancee.',
                'id' => $message->getId(),
                'statutEnvoiMail' => $message->getStatutEnvoiMail(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/reply', name: 'api_message_reply', methods: ['POST'])]
    public function reply(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides.'], 400);
        }

        try {
            $message = $this->messageService->sendReply($id, $data);

            return $this->json([
                'message' => 'Réponse envoyée avec succès.',
                'id' => $message->getId(),
                'status' => $message->getStatus(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.'], 500);
        }
    }
}