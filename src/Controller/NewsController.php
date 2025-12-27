<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NewsController extends AbstractController
{
    private const NEWS = [
        'nouvel-equipement-blanchiment' => [
            'title' => "Nouvel équipement de blanchiment",
            'date' => '15 Juin 2023',
            'summary' => "Acquisition d'un système de blanchiment lampe LED basse chaleur.",
            'content' => "Nous intégrons un système de blanchiment lampe LED basse chaleur : confort amélioré, résultat plus homogène, séance encadrée avec protocole désensibilisant.",
            'bullets' => [
                'Bilan de teinte avant/après',
                'Gel PH neutre pour éviter la sensibilité',
                'Pack entretien pour domicile',
            ],
            'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1470&q=80',
        ],
        'horaires-ete' => [
            'title' => "Horaires d'été",
            'date' => '1er Juillet 2023',
            'summary' => "Horaires d'été du lundi au vendredi de 8h à 18h.",
            'content' => "Ouverture en continu du lundi au vendredi de 8h à 18h. Fermeture exceptionnelle du 10 au 15 août. Prenez rendez-vous en ligne ou par téléphone pour limiter l'attente.",
            'bullets' => [
                'Urgences encaissées sur créneaux dédiés',
                'Planning pédiatrique renforcé le mercredi',
                "Standard joignable jusqu'à 18h",
            ],
            'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1470&q=80',
        ],
        'campagne-prevention' => [
            'title' => 'Campagne de prévention',
            'date' => '5 Septembre 2023',
            'summary' => 'Bilan dentaire gratuit pour toute la famille en septembre.',
            'content' => "Bilan dentaire gratuit pour toute la famille en septembre : hygiène, dépistage carieux, conseils alimentation, avec plan de suivi personnalisé.",
            'bullets' => [
                'Contrôle complet et radios ciblées si besoin',
                'Ateliers brossage pour enfants',
                'Devis transparent pour soins éventuels',
            ],
            'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1470&q=80',
        ],
    ];

    #[Route('/actualites', name: 'news_index')]
    public function index(): Response
    {
        return $this->render('news/index.html.twig', [
            'news' => self::NEWS,
        ]);
    }

    #[Route('/actualites/{slug}', name: 'news_show')]
    public function show(string $slug): Response
    {
        $entry = self::NEWS[$slug] ?? null;

        if ($entry === null) {
            throw new NotFoundHttpException();
        }

        return $this->render('news/show.html.twig', [
            'entry' => $entry,
            'slug' => $slug,
        ]);
    }
}
