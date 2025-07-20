<?php
declare(strict_types=1);

use CancerStan\Fixers\MissingTypeReturnFixer;
use CancerStan\Stan\ErrorMessageDto;
use CancerStan\Stan\FileSummaryDto;
use CancerStan\Persistence\InMemory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;


final class MissingTypeReturnFixerTest extends TestCase
{
    public static function additionProvider(): array
    {
        return [
            ["111", 'int'],
            ["111.50", 'float'],
            ["'testa'", 'string'],
            ["\$this->numericVar", 'int'],
            ["[1,2]", 'array'],
            ["true", 'bool'],
            ["new self()", 'self'],
            ["new TestClass()", 'TestClass'],
            ["\$this->numericMethod()", 'int'],
            ["\$this", 'self'],
        ];
    }

    #[DataProvider('additionProvider')]
    public function testCanSetOutputType(string $return, string $expectedType): void
    {
        $filePath = "TestClass";
        $file = new FileSummaryDto($filePath, []);
        $error = new ErrorMessageDto("Method TestClass::testMethod() has no return type specified.", 3, 0, "missingType.iterableValue");
        $fs = new InMemory();
        CancerStan\CancerStan::$persistence = $fs;
        $method = $this->buildMethod($return);

        $fs->saveFileContents($filePath, $method);

        $fixer = new MissingTypeReturnFixer();
        $success = $fixer->fix($file, $error);

        $this->assertTrue($success);
        $this->assertEquals($this->buildMethod($return, $expectedType), $fs->getFileContents($filePath));
    }

    private function buildMethod(string $return, ?string $expectedReturn = null): string {
        return '<?php
            
            class TestClass {            
                public function testMethod()' . ($expectedReturn ? ': ' . $expectedReturn : '') . ' {
                    return ' . $return . ';
                }
                
                private int $numericVar = 5;
                public function numericMethod(): int {
                    return 11;
                }
            }
        ';
    }
}
