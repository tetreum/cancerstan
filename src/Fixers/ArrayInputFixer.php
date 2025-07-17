<?php

namespace CancerStan\Fixers;

use CancerStan\BaseFixer;
use CancerStan\IFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Traits\FixerTraits;

class ArrayInputFixer extends BaseFixer implements IFixer {

    use FixerTraits;

    public function getErrorIdentifier(): string
    {
        return "missingType.iterableValue";
    }

    public function messageMustContain(): string
    {
        return "with no value type specified in iterable type array";
    }

    public function fix(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        $tmp = explode("as parameter ", $error->message());

        if (count($tmp) != 2) {
            return false;
        }

        $varName = explode(" ", $tmp[1])[0];
        $varNameWithoutDollar = str_replace("$", "", $varName);

        $fileContent = $this->getFileContents($file);
        $affectedLine = $this->getLine($error->line(), $fileContent);

        //  function parse(array|string $filePath) => @param string|string[] $filePath
        if (str_contains($affectedLine, "function ")) {
            $methodName = $this->getMethodNameFromStringLine($affectedLine);
            $params = $this->getMethodParameters($this->getReflectionClass($file)->getMethod($methodName));

            foreach ($params as $param) {
                if ($param->name() == $varNameWithoutDollar) {
                    foreach($param->types() as $type) {
                        if ($type !== 'array') {
                            $this->removeFromCommentMethodAnyLineContaining($varName, $file, $methodName);
                            return $this->addOnMethodComment("@param $type|" . $type . "[] $varName", $file, $methodName);
                        }
                    }
                    return $this->addOnMethodComment("@param mixed[] $varName", $file, $methodName);
                }
            }
            return false;
        }
        return false;
    }
}
