<?php

namespace CancerStan\Fixers;

use CancerStan\BaseFixer;
use CancerStan\IFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Traits\FixerTraits;

class MissingTypeReturnFixer extends BaseFixer implements IFixer {

    use FixerTraits;

    public function getErrorIdentifier(): string
    {
        return "missingType.return";
    }

    public function messageMustContain(): string
    {
        return " has no return type specified.";
    }

    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        preg_match("/Method (.+) has no return type specified/", $error->message(), $matches);
        if (empty($matches[1])) {
            return false;
        }

        // From CountryCollection::getCrawler() => getCrawler
        $methodName = $this->getMethodNameFromAbsolutePath($matches[1]);
        $returnType = $this->getMethodReturnType($file, $methodName);

        if (!$returnType) {
            return false;
        }

        $this->setMethodReturn($file, $methodName, $returnType);

        return true;
    }
}
