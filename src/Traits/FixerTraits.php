<?php

namespace CancerStan\Traits;

use CancerStan\CancerStan;
use CancerStan\Dtos\MethodParameterDto;
use CancerStan\Exceptions\InvalidArgumentException;
use CancerStan\Stan\FileSummaryDto;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

trait FixerTraits {
    private array $lineAlterations = [];

    public function getReflectionClass(FileSummaryDto $file): ReflectionClass {
        // since there's no way to refresh an already loaded class, we just load it again with a new random name
        // that way we can apply multiple changes over the same file
        $fileContent = $this->getFileContents($file);
        $microtime = str_replace(".", "", microtime(true) . rand());
        $newClassName = $file->getClassName() . $microtime;
        $fileContent = str_replace(" " . $file->getClassName(), " " . $newClassName, $fileContent);
        $fileContent = substr($fileContent, 5); // wipe <?php
        eval($fileContent);
        return new ReflectionClass(str_replace($file->getClassName(), $newClassName, $file->getClassWithNameSpace()));
    }

    public function getMethodCode(FileSummaryDto $file, ReflectionMethod $method): string {
        $start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        $source = $this->getFileContentsAsArray($file);
        return implode("", array_slice($source, $start_line, $length));
    }

    public function addOnClassComment(string $thingToAdd, FileSummaryDto $file): bool {
        $fileContent = $this->getFileContents($file);
        $reflectionClass = $this->getReflectionClass($file);
        $existingComment = $reflectionClass->getDocComment();

        if (empty($existingComment)) {
            $startLine = $reflectionClass->getStartLine();
            $originalLine = $this->getLine($startLine, $fileContent);
            $this->addAlteration($originalLine, "/** $thingToAdd */\n" . $originalLine, $file->filePath());
            $fileContent = $this->addBeforeLine($startLine, "/** $thingToAdd */", $fileContent);
        } else {
            $updatedComment = substr($existingComment, 0, strlen($existingComment) -2);
            $updatedComment .= $thingToAdd . " */";

            $fileContent = str_replace($existingComment, $updatedComment, $fileContent);
            $this->addAlteration($existingComment, $updatedComment, $file->filePath());
        }

        $this->saveChanges($file->filePath(), $fileContent);
        return true;
    }

    public function addOnMethodComment(string $thingToAdd, FileSummaryDto $file, string $methodName): bool
    {
        $fileContent = $this->getFileContents($file);
        $reflectionClass = $this->getReflectionClass($file);
        $method = $reflectionClass->getMethod($methodName);
        $existingComment = $method->getDocComment();

        if (empty($existingComment)) {
            $startLine = $method->getStartLine();
            $originalLine = $this->getLine($startLine, $fileContent);
            $this->addAlteration($originalLine, "/** $thingToAdd */\n" . $originalLine, $file->filePath());
            $fileContent = $this->addBeforeLine($startLine, "/** $thingToAdd */", $fileContent);
        } else {
            $updatedComment = substr($existingComment, 0, strlen($existingComment) -2);
            $updatedComment .= $thingToAdd . " */";

            $fileContent = str_replace($existingComment, $updatedComment, $fileContent);
            $this->addAlteration($existingComment, $updatedComment, $file->filePath());
        }

        $this->saveChanges($file->filePath(), $fileContent);
        return true;
    }

    public function removeFromCommentMethodAnyLineContaining(string $keyword, FileSummaryDto $file, string $methodName): bool {
        $fileContent = $this->getFileContents($file);
        $reflectionClass = $this->getReflectionClass($file);
        $method = $reflectionClass->getMethod($methodName);
        $existingComment = $method->getDocComment();
        $lines = explode("\n", $existingComment);
        $gotChanges = false;

        foreach ($lines as $line) {
            if (str_contains($line, $keyword)) {
                unset($lines[array_search($line, $lines)]);
                $gotChanges = true;
            }
        }

        if ($gotChanges) {
            $updatedComment = implode("\n", $lines);
            $fileContent = str_replace($existingComment, $updatedComment, $fileContent);

            $this->addAlteration($existingComment, $updatedComment, $file->filePath());

            $this->saveChanges($file->filePath(), $fileContent);
            return true;
        }
        return false;
    }

    public function addBeforeLine(int $line, string $textToAdd, string $text): string {
        $lines = explode("\n", $text); // Split text into lines

        // Ensure the target line index is within bounds
        if ($line < 1 || $line > count($lines)) {
            throw new InvalidArgumentException("Line number out of range.");
        }

        array_splice($lines, $line - 1, 0, $textToAdd); // Insert text before the target line

        return implode("\n", $lines); // Reconstruct the string
    }

    public function getMethodReturn(string $method): string {
        preg_match_all('/return\s+([^;]+);/', $method, $matches);
        return end($matches[1]) ?? "";
    }

    public function getLine(int $line, string $text): string {
        $lines = explode("\n", $text);
        return $lines[$line - 1];
    }

    public function getMethodNameFromStringLine(string $line): string {
        $methodName = explode("function ", $line)[1];
        return explode("(", $methodName)[0];
    }

    /** @return MethodParameterDto[] */
    public function getMethodParameters(ReflectionMethod $reflectionMethod): array {
        $params = $reflectionMethod->getParameters();
        $list = [];

        foreach ($params as $param) {
            $types = [];
            $type = $param->getType();

            if ($type instanceof ReflectionUnionType) {
                foreach($param->getType()->getTypes() as $subType) {
                    $types[] = $subType->getName();
                }
            } elseif ($type instanceof ReflectionNamedType) {
                $types[] = $type->getName();
            }
            $list[] = new MethodParameterDto(
                name: $param->getName(),
                types: $types,
            );
        }

        return $list;
    }

    public function getLineRange(int $from, int $to, string $text): string {
        $lines = explode("\n", $text); // Split text into lines

        // Ensure line numbers are within valid range
        if ($from < 1 || $to > count($lines) || $from > $to) {
            throw new InvalidArgumentException("Invalid line range.");
        }

        return implode("\n", array_slice($lines, $from - 1, $to - $from + 1));
    }

    public function determineReturnType(FileSummaryDto $file, string $methodReturn): string {
        $methodReturn = trim($methodReturn);

        if (in_array(strtolower($methodReturn), ["true", "false"])) {
            return "bool";
        } else if ($methodReturn === "") {
            return "void";
        } else if (str_starts_with($methodReturn, "'") ||
                    str_starts_with($methodReturn, '"') ||
                    str_ends_with($methodReturn, "::class")) {
            return "string";
        } else if (is_numeric($methodReturn)) {
            return (int)$methodReturn != $methodReturn ? "float" : "int";
        } else if (str_starts_with($methodReturn, '[') ||
                str_starts_with(strtolower($methodReturn), 'array(')) {
            return "array";
        } else if (str_starts_with($methodReturn, 'new ')) {
            // new Xupopter(.,,, => Xupopter
            $className = explode("new ", $methodReturn)[1];
            return explode("(", $className)[0];
        } else if (str_starts_with($methodReturn, '$this->')) {
            // return $this->method() => get the method() output type
            if (preg_match('/^\$this->(\w+)\s*\(.*\)$/', $methodReturn, $matches)) {
                return $this->getMethodReturnType($file, $matches[1]);
            }

            // match properties like $this->total
            if (preg_match('/^\$this->(\w+)$/', $methodReturn, $matches)) {
                $reflectionClass = $this->getReflectionClass($file);
                $propertyReflection = $reflectionClass->getProperty($matches[1]);
                return $propertyReflection->getType()->getName();
            }
        } else if ($methodReturn == '$this') {
            return "self";
        }

        return "";
    }

    public function getMethodNameFromAbsolutePath(string $fullPath): string
    {
        // From CountryCollection::getCrawler() => getCrawler
        $methodName = explode("::", $fullPath);
        $methodName = array_pop($methodName);
        $methodName = str_replace("()", "", $methodName);
        return $methodName;
    }

    public function getMethodDeclaredReturnType(FileSummaryDto $file, string $methodName): string {
        $reflectionClass = $this->getReflectionClass($file);
        $typeMethod = $reflectionClass->getMethod($methodName);
        $type = $typeMethod->getReturnType();

        if (empty($type)) {
            return "";
        }

        $types = [];
        if ($type instanceof ReflectionUnionType) {
            foreach($type->getTypes() as $subType) {
                $types[] = $subType->getName();
            }
        } elseif ($type instanceof ReflectionNamedType) {
            $types[] = $type->getName();
        }

        if (in_array("array", $types)) {
            if (count($types) == 1) {
                return "array";
            }
            $i = array_search("array", $types);
            array_splice($types, $i, 1);
            $types = array_values($types);
            return $types[0];
        }
        return implode("|", $types);
    }

    public function getMethodReturnType(FileSummaryDto $file, string $methodName): string {
        $reflectionClass = $this->getReflectionClass($file);
        $typeMethod = $reflectionClass->getMethod($methodName);
        $typeCode = $this->getMethodCode($file, $typeMethod);
        $rawReturnType = $this->getMethodReturn($typeCode);

        if (!$rawReturnType) {
            $declaredReturn = $this->getMethodDeclaredReturnType($file, $methodName);

            if ($declaredReturn == "array") {
                return $declaredReturn;
            }
        }

        return $this->determineReturnType($file, $rawReturnType);
    }

    public function setMethodReturn(FileSummaryDto $file, string $methodName, string $return): bool
    {
        $reflectionClass = $this->getReflectionClass($file);
        $typeMethod = $reflectionClass->getMethod($methodName);
        $fileContent = $this->getFileContents($file);
        $line = $this->getLine($typeMethod->getStartLine(), $fileContent);

        if (str_contains($line, "):")) {
            return false;
        }

        $fixedLine = str_replace(")", "): " . $return, $line);

        $fileContent = str_replace($line, $fixedLine, $fileContent);
        $this->addAlteration($line, $fixedLine, $file->filePath());

        $this->saveChanges($file->filePath(), $fileContent);
        return true;
    }

    public function addAlteration(string $originalLine, string $fixedLine, string $filePath): void
    {
        if (empty($this->lineAlterations[$filePath])) {
            $this->lineAlterations[$filePath] = [];
        }
        $this->lineAlterations[$filePath][] = [
            "original" => $originalLine,
            "fixed" => $fixedLine,
        ];
    }

    public function getLineAlterations(): array {
        return $this->lineAlterations;
    }

    public function resetLineAlterations(): void {
        $this->lineAlterations = [];
    }

    public function getFileContents(FileSummaryDto $file): string {
        return CancerStan::$persistence->getFileContents($file->filePath());
    }
    public function getFileContentsAsArray(FileSummaryDto $file): array {
        return CancerStan::$persistence->getFileContentsAsArray($file->filePath());
    }

    private function saveChanges(string $filePath, string $content): void
    {
        CancerStan::$persistence->saveFileContents($filePath, $content);
    }
}
