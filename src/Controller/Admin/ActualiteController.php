<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/actualites', name: 'admin_actualites')]
class ActualiteController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('admin/actualites/index.html.twig', [
            'active_page' => 'actualites',
        ]);
    }
}
