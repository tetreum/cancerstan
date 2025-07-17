<?php

namespace CancerStan;

use CancerStan\Persistence\Filesystem;
use CancerStan\Persistence\InMemory;
use CancerStan\Persistence\IPersistence;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Stan\StanOutputDto;
use CancerStan\Traits\CommandLineTrait;
use CancerStan\Traits\FixerTraits;

class CancerStan {

    use FixerTraits, CommandLineTrait;

    public const DRY_RUN_OPTION = "dry-run";
    public const STAN_OPTION = "stan";
    public const CUSTOM_FIXERS_OPTION = "custom-fixers";
    public const COMMAND_OPTION = "command";
    public const HELP_OPTION = "help";

    /** @var IFixer[] $fixers */
    private array $fixers;
    public static array $options;
    public static IPersistence $persistence;

    public function __construct(array $argv, bool $testMode = false)
    {
        self::$options = $this->parseOptions($argv);
        $this->printHeader();

        if (!empty(self::$options[self::COMMAND_OPTION]) || !empty(self::$options[self::HELP_OPTION])) {
            $this->runCommand(empty(self::$options[self::COMMAND_OPTION]) ? self::$options[self::HELP_OPTION] : self::$options[self::COMMAND_OPTION]);
            return;
        }

        $summary = $this->runStan();

        if (!$summary) {
            $this->display("<red>PHPStan didn't return anything :S Did you set the path properly?</red>");
            return;
        }

        if ($summary->filesWithErrorsCount() == 0) {
            return;
        }

        self::$persistence = $testMode ? new InMemory() : new Filesystem();

        $this->getFixers();
        $this->fixErrors($summary);
    }

    public static function isInDryRun(): bool
    {
        return !empty(self::$options[self::DRY_RUN_OPTION]);
    }

    function parseOptions(array $argv): array {
        $result = [];
        $firstI = true;
        foreach ($argv as $arg) {
            if ($firstI) {
                $firstI = false;
                continue;
            }
            if (preg_match('/^--([^=]+)(?:=(.*))?$/', $arg, $matches)) {
                $key = $matches[1];
                $value = $matches[2] ?? true; // Flags like --dry-run will be boolean true
                $result[$key] = $value;
            } else {
                $result["command"] = $arg;
            }
        }
        return $result;
    }

    private function runCommand(string $command): void {
        switch ($command) {
            case "help":
                $availableOptions = [
                    [
                        "name" => "--help",
                        "description" => "Display this help",
                    ],
                    [
                        "name" => "--" . self::DRY_RUN_OPTION,
                        "description" => "Will return the changes to apply without applying them",
                    ],
                    [
                        "name" => "--" . self::STAN_OPTION,
                        "description" => "To provide PHPStan's location. Ex: --" . self::STAN_OPTION . "=/docker/phpstan . Default: /vendor/phpstan",
                    ],
                    [
                        "name" => "--" . self::CUSTOM_FIXERS_OPTION,
                        "description" => "Path to custom fixers directory. Ex: --" . self::CUSTOM_FIXERS_OPTION . "=./MyCustomFixers",
                    ],
                ];

                foreach($availableOptions as $option) {
                    $this->display("<green>" . $option['name'] . "</green>");
                    $this->display($option['description']);
                }
                break;
        }
    }

    private function fixErrors(StanOutputDto $summary): void {
        foreach($summary->files() as $file) {
            $this->display("-- Checking " . $file->filePath() . ": ");

            foreach($file->messages() as $message) {
                $this->display("- Error " . $message->identifier() . " - '" . $message->message() . "'");

                $fixed = $this->runFixersFor($file, $message);

                if (!$fixed) {
                    $this->display("<yellow>No fixers for this error :(</yellow>");
                }
            }
        }
    }

    private function runFixersFor(FileSummaryDto $file, ErrorMessageDto $error): bool
    {
        foreach($this->fixers as $fixer) {
            if ($error->identifier() != $fixer->getErrorIdentifier() ||
                !str_contains($error->message(), $fixer->messageMustContain())
            ) {
                continue;
            }
            $fixed = $fixer->fix($file, $error);

            if ($fixed) {
                $this->display("Fixed by " . $fixer::class);
                foreach($fixer->getAlterations() as $filePath => $alterations) {
                    foreach($alterations as $alteration) {
                        foreach(explode("\n", $alteration['original']) as $line) {
                            $this->display("<red>" . $line . "</red>");
                        }
                        foreach(explode("\n", $alteration['fixed']) as $line) {
                            $this->display("<green>" . $line . "</green>");
                        }

                    }
                }
                $fixer->resetAlterations();
                $this->display("\n");
                return true;
            } else {
                $this->display("<yellow>" . $fixer::class . " tried to fix it without success  :(</yellow>");
            }
        }
        return false;
    }

    private function runStan(): ?StanOutputDto {
        $summary = PHPStan::run(self::$options[self::STAN_OPTION] ?? null);

        if (!$summary) {
            return null;
        }

        $dto = StanOutputDto::fromRaw($summary);

        $this->display("PHPStan found: " . $dto->filesWithErrorsCount() . " errors");

        return $dto;
    }

    public function getFixers(): void {

        $this->loadFixersFrom(__DIR__ . "/Fixers");
        $loadedFixersCount = count($this->fixers);

        $this->display("Loaded Fixers: "  . count($this->fixers));

        if (!empty(self::$options[self::CUSTOM_FIXERS_OPTION])) {
            $this->loadFixersFrom(self::$options[self::CUSTOM_FIXERS_OPTION]);
            $this->display("Loaded Custom Fixers: "  . count($this->fixers) - $loadedFixersCount);
        }
    }

    private function loadFixersFrom(string $folder): void
    {
        $files = scandir($folder);

        foreach ($files as $file) {
            if (in_array($file, [".", ".."])) {
                continue;
            }
            include_once($folder . "/$file");
            $className = "CancerStan\Fixers\\" . str_replace(".php", "", $file);
            $this->fixers[] = new $className();
        }
    }

    private function printHeader(): void
    {
        $this->display("            
            _________                                   _________ __                 
            \_   ___ \_____    ____   ____  ___________/   _____//  |______    ____  
            /    \  \/\__  \  /    \_/ ___\/ __ \_  __ \_____  \\   __\__  \  /    \ 
            \     \____/ __ \|   |  \  \__\  ___/|  | \/        \|  |  / __ \|   |  \
             \______  (____  /___|  /\___  >___  >__| /_______  /|__| (____  /___|  /
                    \/     \/     \/     \/    \/             \/           \/     \/ 
        
        ");
    }
}
