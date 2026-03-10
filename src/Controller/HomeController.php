<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\PersonnelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ActualiteRepository $actualiteRepository, PersonnelRepository $personnelRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'actualites' => $actualiteRepository->findLatestPublished(3),
            'personnels' => $personnelRepository->findOrdered(),
        ]);
    }
}
