<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function upload(UploadedFile $file, string $folder): string
    {
        $extension = $file->guessExtension() ?: 'bin';
        $name = sprintf('%s_%s.%s', date('YmdHis'), bin2hex(random_bytes(6)), $extension);

        $relativeDir = '/uploads/' . trim($folder, '/');
        $targetDir = $this->projectDir . '/public' . $relativeDir;

        if (!$this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir, 0755);
        }

        $file->move($targetDir, $name);

        return $relativeDir . '/' . $name;
    }
}
