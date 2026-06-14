<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewException;
use Lukman\View\Exception\ViewNotFoundException;
use Lukman\View\FileViewFinder;
use Lukman\View\PhpEngine;
use Lukman\View\ViewFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SectionLayoutTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_section_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testSectionCapturesOutputAndDefaultIsUsedWhenMissing(): void
    {
        $this->writeFile('layout.php', '<title><?php echo $section("title", "Default"); ?></title><main><?php echo $section("content"); ?></main>');
        $this->writeFile('home.php', '<?php $extend("layout"); $start("content"); ?>Hello<?php $end(); ?>');

        $this->assertSame('<title>Default</title><main>Hello</main>', $this->factory()->render('home'));
    }

    public function testChildSectionsAreRenderedInsideLayout(): void
    {
        $this->writeFile('layouts/app.php', '<h1><?php echo $section("title"); ?></h1><div><?php echo $section("body"); ?></div>');
        $this->writeFile('pages/home.php', '<?php $extend("layouts.app"); $start("title"); ?>Home<?php $end(); $start("body"); ?>Body<?php $end(); ?>');

        $this->assertSame('<h1>Home</h1><div>Body</div>', $this->factory()->render('pages.home'));
    }

    public function testSectionsAreIsolatedBetweenRenders(): void
    {
        $this->writeFile('layout.php', '<?php echo $section("content", "empty"); ?>');
        $this->writeFile('first.php', '<?php $extend("layout"); $start("content"); ?>First<?php $end(); ?>');
        $this->writeFile('second.php', '<?php $extend("layout"); ?>');
        $factory = $this->factory();

        $this->assertSame('First', $factory->render('first'));
        $this->assertSame('empty', $factory->render('second'));
    }

    public function testEndWithoutStartThrowsViewException(): void
    {
        $this->writeFile('broken.php', '<?php $end(); ?>');

        $this->expectException(ViewException::class);

        $this->factory()->render('broken');
    }

    public function testNestedStartThrowsViewException(): void
    {
        $this->writeFile('broken.php', '<?php $start("one"); $start("two"); ?>');

        $this->expectException(ViewException::class);

        $this->factory()->render('broken');
    }

    public function testSectionBufferIsCleanedWhenRenderFailsBeforeEnd(): void
    {
        $this->writeFile('broken.php', '<?php $start("content"); throw new RuntimeException("broken"); ?>');
        $factory = $this->factory();
        $obLevel = ob_get_level();

        try {
            $factory->render('broken');
            $this->fail('Expected ViewException was not thrown.');
        } catch (ViewException) {
            $this->assertSame($obLevel, ob_get_level());
        }
    }

    public function testMissingLayoutThrowsViewNotFoundException(): void
    {
        $this->writeFile('home.php', '<?php $extend("missing"); ?>');

        $this->expectException(ViewNotFoundException::class);

        $this->factory()->render('home');
    }

    private function factory(): ViewFactory
    {
        return new ViewFactory(new FileViewFinder([$this->tempDir]), new PhpEngine());
    }

    private function writeFile(string $name, string $contents): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

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
