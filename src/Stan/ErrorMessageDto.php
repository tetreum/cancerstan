<?php

namespace CancerStan\Stan;

use \stdClass;

class ErrorMessageDto {
    public function __construct(
        private string $message,
        private int $line,
        private int $ignorable,
        private string $identifier,
    ) {

    }

    public static function fromRaw(stdClass $rawOutput)
    {
        return new self(
            $rawOutput->message,
            $rawOutput->line,
            $rawOutput->ignorable,
            $rawOutput->identifier,
        );
    }

    public function message(): string
    {
        return $this->message;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function ignorable(): int
    {
        return $this->ignorable;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }
}
