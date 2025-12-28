<?php

namespace App\Service;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageService
{
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->validator = $validator;
    }

    public function createMessage(array $data): Message
    {
        // Check if user has more than 3 pending messages of the same type
        $type = $data['type'] ?? 'contact';
        $email = $data['email'];

        $pendingCount = $this->messageRepository->count([
            'email' => $email,
            'type' => $type,
            'status' => 'unread'
        ]);

        if ($pendingCount >= 3) {
            throw new \InvalidArgumentException('Vous avez déjà 3 messages en attente de réponse pour ce type. Veuillez patienter avant d\'en envoyer un nouveau.');
        }

        $message = new Message();

        if (isset($data['firstName'])) {
            $message->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $message->setLastName($data['lastName']);
        }
        if (isset($data['name'])) {
            // For contact form, name might be full name, split if possible
            $names = explode(' ', $data['name'], 2);
            $message->setFirstName($names[0] ?? null);
            $message->setLastName($names[1] ?? null);
        }
        $message->setEmail($data['email']);
        if (isset($data['phone'])) {
            $message->setPhone($data['phone']);
        }
        if (isset($data['subject'])) {
            $message->setSubject($data['subject']);
        }
        if (isset($data['date'])) {
            $message->setAppointmentDate(new \DateTime($data['date']));
        }
        $message->setContent($data['message']);
        $message->setType($type);
        $message->setStatus('unread');

        $errors = $this->validator->validate($message);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Erreurs de validation : ' . implode(', ', $errorMessages));
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function getAllMessages(): array
    {
        return $this->messageRepository->findAll();
    }

    public function getFilteredMessages(?string $type = null, ?string $status = null, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        $qb = $this->messageRepository->createQueryBuilder('m');

        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        if ($status) {
            $qb->andWhere('m.status = :status')->setParameter('status', $status);
        }

        if ($dateStart) {
            $qb->andWhere('m.createdAt >= :dateStart')->setParameter('dateStart', new \DateTime($dateStart));
        }

        if ($dateEnd) {
            $qb->andWhere('m.createdAt <= :dateEnd')->setParameter('dateEnd', new \DateTime($dateEnd . ' 23:59:59'));
        }

        $qb->orderBy('m.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getMessageById(int $id): ?Message
    {
        return $this->messageRepository->find($id);
    }

    public function countMessages(?string $status = null): int
    {
        if ($status) {
            return $this->messageRepository->count(['status' => $status]);
        }
        return $this->messageRepository->count([]);
    }

    public function updateMessage(int $id, array $data): Message
    {
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }

        if (isset($data['status'])) {
            $message->setStatus($data['status']);
        }

        $this->entityManager->flush();
        return $message;
    }

    public function deleteMessage(int $id): void
    {
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }

        $this->entityManager->remove($message);
        $this->entityManager->flush();
    }
}