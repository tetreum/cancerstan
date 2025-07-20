<?php
declare(strict_types=1);

use CancerStan\Fixers\ArrayInputFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Persistence\InMemory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;


final class ArrayInputFixerTest extends TestCase
{
    public static function additionProvider(): array
    {
        return [
            ['array|int', 'int|int[]'],
            ['array', 'mixed[]'],
        ];
    }

    #[DataProvider('additionProvider')]
    public function testCanSetOutputType(string $paramType, string $expectedComment): void
    {
        $filePath = "TestClass";
        $file = new FileSummaryDto($filePath, []);
        $error = new ErrorMessageDto("Method TestClass::testMethod() has parameter \$parameters with no value type specified in iterable type array.", 6, 0, "missingType.iterableValue");
        $fs = new InMemory();
        CancerStan\CancerStan::$persistence = $fs;

        $fs->saveFileContents($filePath, $this->buildMethod($paramType));

        $fixer = new ArrayInputFixer();
        $success = $fixer->fix($file, $error);

        $this->assertTrue($success);
        $this->assertEquals($this->buildMethod($paramType, $expectedComment), $fs->getFileContents($filePath));
    }

    private function buildMethod(string $paramType, ?string $expectedComment = null): string {
        return '<?php
            
            class TestClass {            
            
' . ($expectedComment ? "\n/** @param " . $expectedComment . ' $parameters */' : '') . '
                public function testMethod(' . $paramType . ' $parameters) {
                }
            }
        ';
    }
}
