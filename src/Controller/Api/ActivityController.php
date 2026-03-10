<?php

namespace App\Controller\Api;

use App\Entity\VisitorActivity;
use App\Service\VisitorActivityStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ActivityController extends AbstractController
{
    #[Route('/api/activity/watch', name: 'api_activity_watch', methods: ['POST'])]
    public function watch(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Payload invalide.'], 400);
        }

        $path = (string) ($data['path'] ?? $request->getPathInfo());
        if ($path === '') {
            $path = '/';
        }

        $activity = new VisitorActivity();
        $activity
            ->setEventType((string) ($data['eventType'] ?? 'interaction'))
            ->setPath(mb_substr($path, 0, 255))
            ->setRouteName(isset($data['routeName']) ? (string) $data['routeName'] : null)
            ->setSessionId($request->cookies->get(session_name()))
            ->setIpHash($this->hashIp($request->getClientIp()))
            ->setUserAgent(mb_substr((string) $request->headers->get('User-Agent', ''), 0, 255))
            ->setReferrer(mb_substr((string) $request->headers->get('referer', ''), 0, 255))
            ->setIsAdmin(str_starts_with($path, '/admin'))
            ->setDurationMs(isset($data['durationMs']) ? (int) $data['durationMs'] : null)
            ->setStatusCode(null)
            ->setMethod($request->getMethod())
            ->setMetadata(is_array($data['metadata'] ?? null) ? $data['metadata'] : []);

        $entityManager->persist($activity);
        $entityManager->flush();

        return $this->json(['ok' => true], 201);
    }

    #[Route('/api/admin/dashboard/activity-stats', name: 'api_admin_dashboard_activity_stats', methods: ['GET'])]
    public function adminStats(VisitorActivityStatsService $visitorActivityStatsService): JsonResponse
    {
        return $this->json([
            'data' => $visitorActivityStatsService->getDashboardStats(14),
        ]);
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash('sha256', $ip);
    }
}
