<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/personnels', name: 'admin_personnels')]
class PersonnelController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('admin/personnels/index.html.twig', [
            'active_page' => 'personnels',
        ]);
    }
}
