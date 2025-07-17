<?php

namespace CancerStan;

use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;

interface IFixer {

    public function getErrorIdentifier() : string;
    public function messageMustContain(): string;
    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool;
    public function getAlterations(): array;
    public function resetAlterations(): void;
}
