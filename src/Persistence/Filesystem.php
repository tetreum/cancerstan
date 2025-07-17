<?php

namespace CancerStan\Persistence;


use CancerStan\CancerStan;

class Filesystem implements IPersistence
{
    public function getFileContents(string $filePath): string {
        return file_get_contents($filePath);
    }
    public function getFileContentsAsArray(string $filePath): array {
        return file($filePath);
    }
    public function saveFileContents(string $filePath, string $content): bool {
        if (CancerStan::isInDryRun()) {
            return true;
        }
        file_put_contents($filePath, $content);
        return true;
    }
}
