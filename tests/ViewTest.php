<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\View;
use Lukman\View\PhpEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ViewTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_object_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testConstructorStoresNamePathDataAndEngineIsUsedForRender(): void
    {
        $path = $this->writeFile('profile.php', '<?php echo $name . " " . $role; ?>');
        $view = new View('profile', $path, ['name' => 'Lukman'], new PhpEngine());

        $this->assertSame('profile', $view->name());
        $this->assertSame($path, $view->path());
        $this->assertSame(['name' => 'Lukman'], $view->data());
        $this->assertSame('Lukman Admin', $view->with('role', 'Admin')->render());
    }

    public function testWithArrayMergesDataWithoutLosingInitialData(): void
    {
        $path = $this->writeFile('data.php', '');
        $view = new View('data', $path, ['name' => 'Lukman'], new PhpEngine());

        $this->assertSame(
            ['name' => 'Lukman', 'role' => 'Admin', 'active' => true],
            $view->with(['role' => 'Admin', 'active' => true])->data()
        );
    }

    public function testWithStringKeyKeepsInitialData(): void
    {
        $path = $this->writeFile('data.php', '');
        $view = new View('data', $path, ['name' => 'Lukman'], new PhpEngine());

        $this->assertSame(['name' => 'Lukman', 'role' => 'Admin'], $view->with('role', 'Admin')->data());
    }

    public function testToStringReturnsRenderedString(): void
    {
        $path = $this->writeFile('hello.php', 'Hello, <?php echo $name; ?>');
        $view = new View('hello', $path, ['name' => 'Lukman'], new PhpEngine());

        $this->assertSame('Hello, Lukman', (string) $view);
    }

    public function testToStringReturnsEmptyStringOnRenderFailure(): void
    {
        $view = new View('missing', $this->tempDir . DIRECTORY_SEPARATOR . 'missing.php', [], new PhpEngine());

        $this->assertSame('', (string) $view);
    }

    public function testToStringReturnsEmptyStringWhenTemplateThrowsException(): void
    {
        $path = $this->writeFile('broken.php', '<?php throw new RuntimeException("broken"); ?>');
        $view = new View('broken', $path, [], new PhpEngine());

        $this->assertSame('', (string) $view);
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
