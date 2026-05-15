<?php

namespace App\Service;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageService
{
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;
    private ValidatorInterface $validator;
    private MailerInterface $mailer;
    private SiteConfigurationService $siteConfigurationService;
    private LoggerInterface $logger;
    private string $mailerDsn;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository, ValidatorInterface $validator, MailerInterface $mailer, SiteConfigurationService $siteConfigurationService, LoggerInterface $logger, string $mailerDsn)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->validator = $validator;
        $this->mailer = $mailer;
        $this->siteConfigurationService = $siteConfigurationService;
        $this->logger = $logger;
        $this->mailerDsn = $mailerDsn;
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
        $message->setStatutEnvoiMail('pending');

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

        $this->trySendNotificationEmail($message);

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

    public function getStats(): array
    {
        return [
            'totalMessages' => $this->messageRepository->count([]),
            'unreadMessages' => $this->messageRepository->count(['status' => 'unread']),
            'readMessages' => $this->messageRepository->count(['status' => 'read']),
            'respondedMessages' => $this->messageRepository->count(['status' => 'responded']),
            'mailPending' => $this->messageRepository->count(['statutEnvoiMail' => 'pending']),
            'mailSent' => $this->messageRepository->count(['statutEnvoiMail' => 'sent']),
            'mailFailed' => $this->messageRepository->count(['statutEnvoiMail' => 'failed']),
        ];
    }

    public function updateMessage(int $id, array $data): Message
    {
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }

        if (isset($data['status'])) {
            $status = (string) $data['status'];

            if ($status !== 'read') {
                throw new \InvalidArgumentException('Seul le passage a l\'etat vu est autorise par cette action.');
            }

            if ($message->getStatus() === 'unread') {
                $message->setStatus('read');
            }
        }

        $this->entityManager->flush();
        return $message;
    }

    public function sendReply(int $id, array $data): Message
    {
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }

        $recipientEmail = $message->getEmail();
        if (!is_string($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email du visiteur est invalide.');
        }

        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['message'] ?? ''));

        if ($subject === '') {
            throw new \InvalidArgumentException('Le sujet de la reponse est obligatoire.');
        }

        if ($body === '') {
            throw new \InvalidArgumentException('Le contenu de la reponse est obligatoire.');
        }

        if ($this->isNullTransportEnabled()) {
            throw new \RuntimeException('Le transport mail est desactive pour ce runtime.');
        }

        $senderAddress = $this->siteConfigurationService->getMailSenderAddress();
        $senderName = $this->siteConfigurationService->getMailSenderName();

        if ($senderAddress === null) {
            throw new \RuntimeException('L\'expediteur mail n\'est pas configure.');
        }

        $replyEmail = (new Email())
            ->from(new Address($senderAddress, $senderName))
            ->to($recipientEmail)
            ->replyTo($senderAddress)
            ->subject($subject)
            ->text($this->buildReplyBody($message, $body));

        try {
            $this->logger->info('Sending reply e-mail to visitor.', [
                'messageId' => $message->getId(),
                'recipient' => $recipientEmail,
                'dsn' => $this->getSafeMailerDsnForLogs(),
            ]);

            $this->mailer->send($replyEmail);
            $message->setStatus('responded');
            $this->entityManager->flush();

            return $message;
        } catch (TransportExceptionInterface|\Throwable $exception) {
            $this->logger->error('Reply e-mail failed to send.', [
                'messageId' => $message->getId(),
                'recipient' => $recipientEmail,
                'error' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('L\'envoi de la reponse par e-mail a echoue.');
        }
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

    public function retryMailDelivery(int $id): Message
    {
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \Exception('Message not found');
        }

        $this->trySendNotificationEmail($message);

        return $message;
    }

    private function trySendNotificationEmail(Message $message): void
    {
        $recipients = $this->siteConfigurationService->getMailRecipients();
        $senderAddress = $this->siteConfigurationService->getMailSenderAddress();
        $senderName = $this->siteConfigurationService->getMailSenderName();

        if ($this->isNullTransportEnabled()) {
            $message->setStatutEnvoiMail('failed');
            $this->logger->warning('Notification e-mail not sent: null transport is enabled for current runtime.', [
                'messageId' => $message->getId(),
                'dsn' => $this->getSafeMailerDsnForLogs(),
            ]);
            $this->entityManager->flush();

            return;
        }

        if ($recipients === [] || $senderAddress === null) {
            $message->setStatutEnvoiMail('failed');
            $this->logger->warning('Notification e-mail not sent: sender or recipients are not configured.', [
                'messageId' => $message->getId(),
                'hasRecipients' => $recipients !== [],
                'hasSender' => $senderAddress !== null,
            ]);
            $this->entityManager->flush();

            return;
        }

        $email = (new Email())
            ->from(new Address($senderAddress, $senderName))
            ->to(...$recipients)
            ->subject(sprintf('Nouveau message (%s) - %s', $message->getType(), $message->getEmail()))
            ->text($this->buildMailBody($message));

        $visitorEmail = $message->getEmail();
        if (is_string($visitorEmail) && filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
            $email->replyTo($visitorEmail);
        }

        try {
            $this->logger->info('Sending notification e-mail.', [
                'messageId' => $message->getId(),
                'type' => $message->getType(),
                'recipients' => $recipients,
                'dsn' => $this->getSafeMailerDsnForLogs(),
            ]);

            $this->mailer->send($email);
            $message->setStatutEnvoiMail('sent');
        } catch (TransportExceptionInterface|\Throwable $exception) {
            $message->setStatutEnvoiMail('failed');
            $this->logger->error('Notification e-mail failed to send.', [
                'messageId' => $message->getId(),
                'type' => $message->getType(),
                'error' => $exception->getMessage(),
            ]);
        }

        $this->entityManager->flush();
    }

    private function isNullTransportEnabled(): bool
    {
        return str_starts_with(trim($this->mailerDsn), 'null://');
    }

    private function getSafeMailerDsnForLogs(): string
    {
        $dsn = trim($this->mailerDsn);
        $parts = parse_url($dsn);

        if ($parts === false) {
            return $dsn;
        }

        $scheme = $parts['scheme'] ?? 'unknown';
        $host = $parts['host'] ?? 'n/a';

        return sprintf('%s://%s', $scheme, $host);
    }

    private function buildMailBody(Message $message): string
    {
        return sprintf(
            "Nouveau message recu\n\nNom: %s %s\nEmail: %s\nTelephone: %s\nType: %s\nSujet: %s\nDate RDV: %s\nMessage:\n%s",
            $message->getFirstName() ?? '',
            $message->getLastName() ?? '',
            $message->getEmail() ?? '',
            $message->getPhone() ?? 'N/A',
            $message->getType() ?? '',
            $message->getSubject() ?? 'N/A',
            $message->getAppointmentDate()?->format('Y-m-d') ?? 'N/A',
            $message->getContent() ?? ''
        );
    }

    private function buildReplyBody(Message $message, string $replyContent): string
    {
        return sprintf(
            "%s\n\n---\nMessage original de %s %s (%s)\nSujet: %s\nDate: %s\n\n%s",
            $replyContent,
            $message->getFirstName() ?? '',
            $message->getLastName() ?? '',
            $message->getEmail() ?? '',
            $message->getSubject() ?? 'N/A',
            $message->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'N/A',
            $message->getContent() ?? ''
        );
    }
}