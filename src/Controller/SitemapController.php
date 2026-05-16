<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap_xml')]
    public function index(ActualiteRepository $actualiteRepository): Response
    {
        $urls = [
            [
                'loc' => $this->generateUrl('app_home'),
                'lastmod' => null,
                'priority' => '1.0',
            ],
            [
                'loc' => $this->generateUrl('section_about'),
                'lastmod' => null,
                'priority' => '0.8',
            ],
            [
                'loc' => $this->generateUrl('section_team'),
                'lastmod' => null,
                'priority' => '0.8',
            ],
            [
                'loc' => $this->generateUrl('services_index'),
                'lastmod' => null,
                'priority' => '0.9',
            ],
            [
                'loc' => $this->generateUrl('news_index'),
                'lastmod' => null,
                'priority' => '0.9',
            ],
            [
                'loc' => $this->generateUrl('section_appointment'),
                'lastmod' => null,
                'priority' => '0.7',
            ],
            [
                'loc' => $this->generateUrl('section_location'),
                'lastmod' => null,
                'priority' => '0.7',
            ],
            [
                'loc' => $this->generateUrl('section_contact'),
                'lastmod' => null,
                'priority' => '0.8',
            ],
        ];

        foreach ($actualiteRepository->findPublished() as $entry) {
            $urls[] = [
                'loc' => $this->generateUrl('news_show', ['slug' => $entry->getSlug()]),
                'lastmod' => $entry->getUpdatedAt()?->format('Y-m-d'),
                'priority' => '0.8',
            ];
        }

        return new Response(
            $this->renderView('sitemap.xml.twig', ['urls' => $urls]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml']
        );
    }
}
