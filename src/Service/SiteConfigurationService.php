<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class SiteConfigurationService
{
    private string $configPath;
    private Filesystem $filesystem;

    public function __construct(string $projectDir, Filesystem $filesystem)
    {
        $this->configPath = $projectDir . '/config/site_configuration.json';
        $this->filesystem = $filesystem;
    }

    public function getAll(): array
    {
        if (!$this->filesystem->exists($this->configPath)) {
            return [];
        }

        $raw = file_get_contents($this->configPath);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function save(array $configuration): void
    {
        $json = json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Impossible d\'encoder la configuration du site en JSON.');
        }

        $this->filesystem->dumpFile($this->configPath, $json . PHP_EOL);
    }

    public function getMailRecipient(): ?string
    {
        $config = $this->getAll();

        return $config['mail']['recipient'] ?? $config['contact']['email'] ?? null;
    }
}
