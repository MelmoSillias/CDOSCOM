<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/configurations', name: 'admin_configurations')]
class ConfigurationController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('admin/configurations/index.html.twig', [
            'active_page' => 'configurations',
        ]);
    }
}
