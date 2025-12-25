<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SectionController extends AbstractController
{
    #[Route('/a-propos', name: 'section_about')]
    public function about(): Response
    {
        return $this->render('sections/about.html.twig');
    }

    #[Route('/services', name: 'section_services')]
    public function services(): Response
    {
        return $this->render('sections/services.html.twig');
    }

    #[Route('/equipe', name: 'section_team')]
    public function team(): Response
    {
        return $this->render('sections/team.html.twig');
    }

    #[Route('/actualites', name: 'section_news')]
    public function news(): Response
    {
        return $this->render('sections/news.html.twig');
    }

    #[Route('/rendez-vous', name: 'section_appointment')]
    public function appointment(): Response
    {
        return $this->render('sections/appointment.html.twig');
    }

    #[Route('/localisation', name: 'section_location')]
    public function location(): Response
    {
        return $this->render('sections/location.html.twig');
    }

    #[Route('/contact', name: 'section_contact')]
    public function contact(): Response
    {
        return $this->render('sections/contact.html.twig');
    }
}
