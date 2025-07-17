<?php

namespace CancerStan\Dtos;


class MethodParameterDto {
    public function __construct(public string $name,
                                public array $types) {

    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return string[] */
    public function types(): array
    {
        return $this->types;
    }
}
