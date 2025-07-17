<?php

namespace CancerStan\Traits;

trait CommandLineTrait {
    private array $colorTags = [
        "/<red>(.*)?<\/red>/",
        "/<yellow>(.*)?<\/yellow>/",
        "/<green>(.*)?<\/green>/",
    ];
    private array $colorReplacements = [
        "\033[31m$1\033[0m",
        "\033[33m$1\033[0m",
        "\033[32m$1\033[0m",
    ];

    public function display(string $text): void
    {
        p(preg_replace($this->colorTags, $this->colorReplacements, $text));
    }
}
