<?php
declare(strict_types=1);

use CancerStan\Persistence\InMemory;
use PHPUnit\Framework\TestCase;

final class InMemoryFileSystemTest extends TestCase
{
    public function testCanSaveFiles(): void
    {
        $inMemory = new InMemory();
        $filePath = "test/testa.php";
        $expectedContent = "<?php echo 'hello';";

        $inMemory->saveFileContents($filePath, $expectedContent);

        $content = $inMemory->getFileContents($filePath);
        $this->assertEquals($expectedContent, $content);
    }

    public function testCanOverwriteFiles(): void
    {
        $inMemory = new InMemory();
        $filePath = "test/testa.php";
        $unexpectedContent = "<?php echo 'hi';";
        $expectedContent = "<?php echo 'hello';";

        $inMemory->saveFileContents($filePath, $unexpectedContent);
        $inMemory->saveFileContents($filePath, $expectedContent);

        $content = $inMemory->getFileContents($filePath);
        $this->assertEquals($expectedContent, $content);
    }
}


