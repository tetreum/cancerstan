<?php

namespace CancerStan\Persistence;



interface IPersistence
{
    public function getFileContents(string $filePath): string;
    public function getFileContentsAsArray(string $filePath): array;
    public function saveFileContents(string $filePath, string $content): bool;
}
