<?php

namespace App\Twig;

use App\Service\SiteConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteConfigurationExtension extends AbstractExtension
{
    private SiteConfigurationService $siteConfigurationService;

    public function __construct(SiteConfigurationService $siteConfigurationService)
    {
        $this->siteConfigurationService = $siteConfigurationService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_configuration', [$this, 'getSiteConfiguration']),
        ];
    }

    public function getSiteConfiguration(): array
    {
        return $this->siteConfigurationService->getAll();
    }
}
