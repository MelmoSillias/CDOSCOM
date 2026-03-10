<?php

namespace App\EventSubscriber;

use App\Entity\VisitorActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('_watcher_started_at', microtime(true));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $path = $request->getPathInfo();
        if ($this->shouldIgnore($path, (string) $response->headers->get('Content-Type'))) {
            return;
        }

        $startedAt = (float) $request->attributes->get('_watcher_started_at', microtime(true));
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $activity = new VisitorActivity();
        $activity
            ->setEventType('page_view')
            ->setPath($path)
            ->setRouteName($request->attributes->get('_route'))
            ->setSessionId($request->cookies->get(session_name()))
            ->setIpHash($this->hashIp($request->getClientIp()))
            ->setUserAgent(mb_substr((string) $request->headers->get('User-Agent', ''), 0, 255))
            ->setReferrer(mb_substr((string) $request->headers->get('referer', ''), 0, 255))
            ->setIsAdmin(str_starts_with($path, '/admin'))
            ->setDurationMs($durationMs)
            ->setStatusCode($response->getStatusCode())
            ->setMethod($request->getMethod())
            ->setMetadata([]);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }

    private function shouldIgnore(string $path, string $contentType): bool
    {
        if (str_starts_with($path, '/_profiler') || str_starts_with($path, '/_wdt')) {
            return true;
        }

        if (str_starts_with($path, '/assets') || str_starts_with($path, '/build')) {
            return true;
        }

        if (str_starts_with($path, '/api/activity/watch')) {
            return true;
        }

        $isHtml = str_contains($contentType, 'text/html') || $contentType === '';

        return !$isHtml;
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash('sha256', $ip);
    }
}
