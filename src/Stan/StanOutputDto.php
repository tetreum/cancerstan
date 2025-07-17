<?php

namespace CancerStan\Stan;

use \stdClass;

class StanOutputDto {
    public function __construct(
        private int $totalErrorsCount,
        private int $filesWithErrorsCount,
        private array $files,
    ) {

    }

    public static function fromRaw(stdClass $rawOutput): self
    {
        $list = [];

        foreach ($rawOutput->files as $filePath => $summary) {
            $list[] = FileSummaryDto::fromRaw($filePath, $summary);
        }

        return new self(
            $rawOutput->totals->errors,
            $rawOutput->totals->file_errors,
            $list
        );
    }

    public function totalErrorsCount(): int
    {
        return $this->totalErrorsCount;
    }

    public function filesWithErrorsCount(): int
    {
        return $this->filesWithErrorsCount;
    }

    /** @return FileSummaryDto[] */
    public function files(): array
    {
        return $this->files;
    }
}
