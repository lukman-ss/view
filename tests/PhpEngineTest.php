<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewException;
use Lukman\View\Exception\ViewNotFoundException;
use Lukman\View\PhpEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PhpEngineTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_engine_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testRenderReturnsBufferedOutput(): void
    {
        $path = $this->writeFile('hello.php', 'Hello, <?php echo $name; ?>');
        $engine = new PhpEngine();

        $this->expectOutputString('');

        $this->assertSame('Hello, Lukman', $engine->render($path, ['name' => 'Lukman']));
    }

    public function testDataCannotOverwriteInternalVariables(): void
    {
        $path = $this->writeFile('internal.php', '<?php echo basename($__path); ?>');
        $engine = new PhpEngine();

        $this->assertSame('internal.php', $engine->render($path, ['__path' => 'wrong.php']));
    }

    public function testMissingFileThrowsViewNotFoundException(): void
    {
        $engine = new PhpEngine();

        $this->expectException(ViewNotFoundException::class);

        $engine->render($this->tempDir . DIRECTORY_SEPARATOR . 'missing.php');
    }

    public function testTemplateExceptionIsWrappedAndBuffersAreCleaned(): void
    {
        $path = $this->writeFile('broken.php', 'before <?php throw new RuntimeException("broken template"); ?>');
        $engine = new PhpEngine();
        $obLevel = ob_get_level();

        try {
            $engine->render($path);
            $this->fail('Expected ViewException was not thrown.');
        } catch (ViewException $exception) {
            $this->assertSame($obLevel, ob_get_level());
            $this->assertSame('', $this->getActualOutputForAssertion());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
            $this->assertStringContainsString('broken template', $exception->getMessage());
        }
    }

    public function testEscapeAndRawHelpersAreAvailableInTemplate(): void
    {
        $path = $this->writeFile(
            'helpers.php',
            '<?php echo $e($html) . "|" . $raw($html) . "|" . $e(null); ?>'
        );
        $engine = new PhpEngine();

        $this->assertSame(
            '&lt;strong&gt;&quot;Hello&quot;&lt;/strong&gt;|<strong>"Hello"</strong>|',
            $engine->render($path, ['html' => '<strong>"Hello"</strong>'])
        );
    }

    public function testUserDataCannotOverrideInternalHelpers(): void
    {
        $path = $this->writeFile('helper-override.php', '<?php echo $e($html) . $raw($html); ?>');
        $engine = new PhpEngine();

        $this->assertSame(
            '&lt;b&gt;x&lt;/b&gt;<b>x</b>',
            $engine->render($path, [
                'html' => '<b>x</b>',
                'e' => 'not-callable',
                'raw' => 'not-callable',
            ])
        );
    }

    private function writeFile(string $name, string $contents): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $contents);

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}
