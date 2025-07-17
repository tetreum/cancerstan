<?php

namespace CancerStan\Fixers;

use CancerStan\BaseFixer;
use CancerStan\IFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Traits\FixerTraits;

class ArrayOutputFixer extends BaseFixer implements IFixer {

    use FixerTraits;

    public function getErrorIdentifier(): string
    {
        return "missingType.iterableValue";
    }

    public function messageMustContain(): string
    {
        return "return type has no value type specified in iterable type array";
    }

    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        preg_match("/Method (.+) return type has no value type specified in iterable type array/", $error->message(), $matches);
        if (empty($matches[1])) {
            return false;
        }

        // From CountryCollection::getCrawler() => getCrawler
        $methodName = explode("::", $matches[1]);
        $methodName = array_pop($methodName);
        $methodName = str_replace("()", "", $methodName);

        $returnType = $this->getMethodDeclaredReturnType($file, $methodName);

        return $this->addOnMethodComment("@return array<" . ($returnType == "array" ? 'mixed' : $returnType) . ">", $file, $methodName);
    }
}
