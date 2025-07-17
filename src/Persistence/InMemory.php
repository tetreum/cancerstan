<?php

namespace CancerStan\Persistence;


use CancerStan\CancerStan;

class InMemory implements IPersistence
{
    private array $data = [];
    public function getFileContents(string $filePath): string {
        return $this->data[$filePath] ?? '';
    }
    public function getFileContentsAsArray(string $filePath): array {
        return explode("\n", $this->data[$filePath]) ?? [];
    }
    public function saveFileContents(string $filePath, string $content): bool {
        if (CancerStan::isInDryRun()) {
            return true;
        }
        $this->data[$filePath] = $content;
        return true;
    }
}
