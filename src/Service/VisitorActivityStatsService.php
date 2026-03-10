<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class VisitorActivityStatsService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDashboardStats(int $days = 14): array
    {
        $since = (new \DateTimeImmutable(sprintf('-%d days', $days - 1)))->setTime(0, 0, 0)->format('Y-m-d H:i:s');

        $kpis = $this->connection->fetchAssociative(
            'SELECT COUNT(*) AS events,
                    COUNT(DISTINCT COALESCE(session_id, ip_hash)) AS unique_visitors,
                    SUM(CASE WHEN event_type = :pageView THEN 1 ELSE 0 END) AS page_views,
                    AVG(CASE WHEN duration_ms IS NULL THEN NULL ELSE duration_ms END) AS avg_duration
             FROM visitor_activity
             WHERE is_admin = 0 AND created_at >= :since',
            ['pageView' => 'page_view', 'since' => $since]
        );

        $daily = $this->connection->fetchAllAssociative(
            'SELECT DATE(created_at) AS day, COUNT(*) AS views
             FROM visitor_activity
             WHERE is_admin = 0 AND event_type = :pageView AND created_at >= :since
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            ['pageView' => 'page_view', 'since' => $since]
        );

        $topPages = $this->connection->fetchAllAssociative(
            'SELECT path, COUNT(*) AS hits
             FROM visitor_activity
             WHERE is_admin = 0 AND event_type = :pageView AND created_at >= :since
             GROUP BY path
             ORDER BY hits DESC
             LIMIT 8',
            ['pageView' => 'page_view', 'since' => $since]
        );

        $eventBreakdown = $this->connection->fetchAllAssociative(
            'SELECT event_type, COUNT(*) AS total
             FROM visitor_activity
             WHERE is_admin = 0 AND created_at >= :since
             GROUP BY event_type
             ORDER BY total DESC',
            ['since' => $since]
        );

        return [
            'kpis' => [
                'events' => (int) ($kpis['events'] ?? 0),
                'uniqueVisitors' => (int) ($kpis['unique_visitors'] ?? 0),
                'pageViews' => (int) ($kpis['page_views'] ?? 0),
                'avgDurationMs' => (int) round((float) ($kpis['avg_duration'] ?? 0)),
            ],
            'daily' => $daily,
            'topPages' => $topPages,
            'eventBreakdown' => $eventBreakdown,
        ];
    }
}
