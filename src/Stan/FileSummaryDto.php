<?php

namespace CancerStan\Stan;

use CancerStan\CancerStan;
use \stdClass;

class FileSummaryDto {

    private string $fileName;

    public function __construct(
        private string $filePath,
        private array  $messages,
    ) {
        $this->fileName = pathinfo($this->filePath)['filename'];
    }

    public static function fromRaw(string $fileName, stdClass $rawOutput)
    {
        $list = [];

        foreach ($rawOutput->messages as $message) {
            $list[] = ErrorMessageDto::fromRaw($message);
        }

        return new self(
            $fileName,
            $list
        );
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function getClassName(): string
    {
        return str_replace(".php", "", $this->fileName());
    }
    public function getClassWithNameSpace(): string
    {
        $content = CancerStan::$persistence->getFileContents($this->filePath);
        $tmp = explode("namespace", $content);
        $nameSpace = empty($tmp[1]) ? "" : explode(";", $tmp[1])[0];
        return trim($nameSpace . '\\' . str_replace(".php", "", $this->fileName()));
    }

    /** @return ErrorMessageDto[] */
    public function messages(): array
    {
        return $this->messages;
    }
}
