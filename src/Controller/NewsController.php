<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NewsController extends AbstractController
{
    #[Route('/actualites', name: 'news_index')]
    public function index(ActualiteRepository $actualiteRepository): Response
    {
        return $this->render('news/index.html.twig', [
            'news' => $actualiteRepository->findPublished(),
        ]);
    }

    #[Route('/actualites/{slug}', name: 'news_show')]
    public function show(string $slug, ActualiteRepository $actualiteRepository): Response
    {
        $entry = $actualiteRepository->findOneBy(['slug' => $slug]);

        if ($entry === null) {
            throw new NotFoundHttpException();
        }

        return $this->render('news/show.html.twig', [
            'entry' => $entry,
            'slug' => $slug,
        ]);
    }
}
