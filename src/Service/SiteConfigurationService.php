<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class SiteConfigurationService
{
    private string $configPath;
    private Filesystem $filesystem;
    private string $mailSenderAddress;
    private string $mailSenderName;
    private string $mailNotificationRecipients;

    public function __construct(
        string $projectDir,
        Filesystem $filesystem,
        string $mailSenderAddress,
        string $mailSenderName,
        string $mailNotificationRecipients,
    )
    {
        $this->configPath = $projectDir . '/config/site_configuration.json';
        $this->filesystem = $filesystem;
        $this->mailSenderAddress = $mailSenderAddress;
        $this->mailSenderName = $mailSenderName;
        $this->mailNotificationRecipients = $mailNotificationRecipients;
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
        $envRecipients = $this->parseRecipients($this->mailNotificationRecipients);
        if ($envRecipients !== []) {
            return $envRecipients[0];
        }

        $config = $this->getAll();

        return $config['mail']['recipient'] ?? $config['contact']['email'] ?? null;
    }

    /**
     * @return string[]
     */
    public function getMailRecipients(): array
    {
        $config = $this->getAll();

        $envRecipients = $this->parseRecipients($this->mailNotificationRecipients);
        if ($envRecipients !== []) {
            return $envRecipients;
        }

        $configRecipients = $config['mail']['recipients'] ?? ($config['mail']['recipient'] ?? null);
        $recipients = $this->parseRecipients($configRecipients);

        $recipients = array_values(array_unique($recipients));

        if ($recipients !== []) {
            return $recipients;
        }

        $fallbackRecipient = $this->getMailRecipient();

        return $fallbackRecipient ? $this->parseRecipients($fallbackRecipient) : [];
    }

    public function getMailSenderAddress(): ?string
    {
        $config = $this->getAll();

        $senderAddress = $this->mailSenderAddress
            ?: ($config['mail']['sender_address'] ?? null)
            ?: ($config['contact']['email'] ?? null);

        if (!is_string($senderAddress)) {
            return null;
        }

        $senderAddress = trim($senderAddress);

        return filter_var($senderAddress, FILTER_VALIDATE_EMAIL) ? $senderAddress : null;
    }

    public function getMailSenderName(): string
    {
        $config = $this->getAll();

        $senderName = $this->mailSenderName
            ?: ($config['mail']['sender_name'] ?? null)
            ?: 'CDOSCOM';

        return is_string($senderName) ? trim($senderName) : 'CDOSCOM';
    }

    /**
     * @param mixed $source
     * @return string[]
     */
    private function parseRecipients(mixed $source): array
    {
        if (is_string($source)) {
            $source = explode(',', $source);
        }

        if (!is_array($source)) {
            return [];
        }

        $emails = [];

        foreach ($source as $value) {
            if (!is_string($value)) {
                continue;
            }

            $candidate = trim($value);
            if ($candidate === '') {
                continue;
            }

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $candidate;
            }
        }

        return $emails;
    }
}
