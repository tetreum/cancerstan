<?php

namespace CancerStan\Fixers;

use CancerStan\BaseFixer;
use CancerStan\IFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Traits\FixerTraits;

class MissingTypeInputFixer extends BaseFixer implements IFixer {

    use FixerTraits;

    public function getErrorIdentifier(): string
    {
        return "missingType.parameter";
    }

    public function messageMustContain(): string
    {
        return " with no type specified.";
    }

    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        preg_match("/Method (.+) has parameter/", $error->message(), $methodMatches);
        if (empty($methodMatches[1])) {
            return false;
        }
        $methodName = $this->getMethodNameFromAbsolutePath($methodMatches[1]);

        preg_match("/has parameter (.+) with no type specified/", $error->message(), $matches);
        if (empty($matches[1])) {
            return false;
        }

        $varToFind = $matches[1];

        $reflectionClass = $this->getReflectionClass($file);
        $typeMethod = $reflectionClass->getMethod($methodName);
        $code = $this->removeFirstAndLastLine($this->getMethodCode($file, $typeMethod));
        $expression = $this->extractExpressionUsingVariable($varToFind, $code);
        // Stil WIP
        return false;
    }

    function removeFirstAndLastLine(string $input): string {
        $lines = explode("\n", trim($input));

        if (count($lines) < 3) {
            return '';
        }

        $middleLines = array_slice($lines, 1, -1);
        return implode("\n", $middleLines);
    }

    private function extractExpressionUsingVariable(string $varToFind, string $code): ?string {
        // Remove newlines for easier processing
        $flatCode = str_replace(["\n", "\r"], ' ', $code);

        // Match any parenthetical or arithmetic expression that contains the variable
        $pattern = '/([^\s;()]*\([^)]*' . preg_quote($varToFind, '/') . '[^)]*\)|[^;]*' . preg_quote($varToFind, '/') . '[^;]*)/';

        if (preg_match_all($pattern, $flatCode, $matches)) {
            foreach ($matches[0] as $match) {
                if (strpos($match, $varToFind) !== false) {
                    return trim($match);
                }
            }
        }

        return null;
    }
}
