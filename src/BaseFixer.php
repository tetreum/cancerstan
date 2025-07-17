<?php

namespace CancerStan;

use CancerStan\Traits\FixerTraits;

class BaseFixer {

    use FixerTraits;

    public function getAlterations(): array
    {
        return $this->getLineAlterations();
    }

    public function resetAlterations(): void
    {
        $this->resetLineAlterations();
    }
}
