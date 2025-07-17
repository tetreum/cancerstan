<?php

namespace CancerStan\Fixers;

use CancerStan\BaseFixer;
use CancerStan\IFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Traits\FixerTraits;

class JsonSerializeReturnTypeFixer extends BaseFixer implements IFixer {

    use FixerTraits;

    public function getErrorIdentifier(): string
    {
        return "missingType.iterableValue";
    }

    public function messageMustContain(): string
    {
        return "::jsonSerialize() return type has no value type specified in iterable type array";
    }

    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        return $this->addOnMethodComment("@return array<mixed>", $file, "jsonSerialize");
    }
}
