<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ServicesController extends AbstractController
{
    private const SERVICES = [
        'soins-dentaires' => [
            'title' => 'Soins dentaires',
            'partial' => 'sections/_details-services-soins.html.twig',
        ],
        'extractions' => [
            'title' => 'Extractions dentaires',
            'partial' => 'sections/_details-services-extractions.html.twig',
        ],
        'protheses' => [
            'title' => 'Prothèses dentaires',
            'partial' => 'sections/_details-services-protheses.html.twig',
        ],
        'blanchiment' => [
            'title' => 'Blanchiment dentaire',
            'partial' => 'sections/_details-services-blanchiment.html.twig',
        ],
        'radiographie' => [
            'title' => 'Radiographie dentaire',
            'partial' => 'sections/_details-services-radiographie.html.twig',
        ],
        'esthetique' => [
            'title' => 'Dentisterie esthétique',
            'partial' => 'sections/_details-services-esthetique.html.twig',
        ],
        'pediatrique' => [
            'title' => 'Dentisterie pédiatrique',
            'partial' => 'sections/_details-services-pediatrique.html.twig',
        ],
    ];

    #[Route('/services', name: 'services_index')]
    public function index(): Response
    {
        return $this->render('services/index.html.twig', [
            'services' => self::SERVICES,
        ]);
    }

    #[Route('/services/{slug}', name: 'service_show')]
    public function show(string $slug): Response
    {
        $service = self::SERVICES[$slug] ?? null;

        if ($service === null) {
            throw new NotFoundHttpException();
        }

        return $this->render('services/show.html.twig', [
            'service' => $service,
            'slug' => $slug,
        ]);
    }
}
