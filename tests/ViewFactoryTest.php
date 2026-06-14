<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\FileViewFinder;
use Lukman\View\PhpEngine;
use Lukman\View\View;
use Lukman\View\ViewFactory;
use PHPUnit\Framework\TestCase;

class ViewFactoryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_factory_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testMakeUsesFinderAndReturnsView(): void
    {
        $path = $this->writeFile('pages/home.php', 'Home');
        $factory = $this->factory();

        $view = $factory->make('pages.home');

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('pages.home', $view->name());
        $this->assertSame($path, $view->path());
    }

    public function testRenderShortcutsToMakeRender(): void
    {
        $this->writeFile('hello.php', 'Hello, <?php echo $name; ?>');

        $this->assertSame('Hello, Lukman', $this->factory()->render('hello', ['name' => 'Lukman']));
    }

    public function testSharedDataIsPassedToViewAndViewDataOverridesSharedData(): void
    {
        $this->writeFile('profile.php', '<?php echo $name . " " . $role; ?>');
        $factory = $this->factory();
        $factory->share('name', 'Shared');
        $factory->share(['role' => 'Member']);

        $view = $factory->make('profile', ['name' => 'Lukman']);

        $this->assertSame(['name' => 'Lukman', 'role' => 'Member'], $view->data());
        $this->assertSame('Lukman Member', $view->render());
    }

    public function testShareStringAndArrayUpdateSharedData(): void
    {
        $factory = $this->factory();
        $factory->share('name', 'Lukman');
        $factory->share(['role' => 'Admin']);

        $this->assertSame(['name' => 'Lukman', 'role' => 'Admin'], $factory->shared());
    }

    public function testExistsFinderAndEngineProxyCorrectInstances(): void
    {
        $this->writeFile('home.php', '');
        $finder = new FileViewFinder([$this->tempDir]);
        $engine = new PhpEngine();
        $factory = new ViewFactory($finder, $engine);

        $this->assertTrue($factory->exists('home'));
        $this->assertFalse($factory->exists('missing'));
        $this->assertSame($finder, $factory->finder());
        $this->assertSame($engine, $factory->engine());
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
