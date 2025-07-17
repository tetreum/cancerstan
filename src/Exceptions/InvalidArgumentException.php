<?php

namespace CancerStan\Exceptions;

class InvalidArgumentException extends \LogicException {
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
