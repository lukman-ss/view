<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewNotFoundException;
use Lukman\View\FileViewFinder;
use Lukman\View\PhpEngine;
use Lukman\View\ViewFactory;
use PHPUnit\Framework\TestCase;

class IncludePartialTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_include_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testIncludeUsesFinderAndParentData(): void
    {
        $this->writeFile('home.php', 'Home <?php echo $include("partials.card"); ?>');
        $this->writeFile('partials/card.php', 'Card <?php echo $name; ?>');

        $this->assertSame('Home Card Lukman', $this->factory()->render('home', ['name' => 'Lukman']));
    }

    public function testIncludeAdditionalDataOverridesParentData(): void
    {
        $this->writeFile('home.php', '<?php echo $include("partials.card", ["name" => "Override"]); ?>');
        $this->writeFile('partials/card.php', '<?php echo $name; ?>');

        $this->assertSame('Override', $this->factory()->render('home', ['name' => 'Parent']));
    }

    public function testNestedIncludeWorks(): void
    {
        $this->writeFile('home.php', 'A <?php echo $include("partials.outer"); ?>');
        $this->writeFile('partials/outer.php', 'B <?php echo $include("partials.inner"); ?>');
        $this->writeFile('partials/inner.php', 'C <?php echo $name; ?>');

        $this->assertSame('A B C Lukman', $this->factory()->render('home', ['name' => 'Lukman']));
    }

    public function testNamespacedIncludeWorks(): void
    {
        $shared = $this->makeDirectory('shared');
        $this->writeFile('shared/card.php', 'Card <?php echo $name; ?>');
        $this->writeFile('home.php', '<?php echo $include("shared::card"); ?>');
        $finder = new FileViewFinder([$this->tempDir]);
        $finder->addNamespace('shared', $shared);

        $factory = new ViewFactory($finder, new PhpEngine());

        $this->assertSame('Card Lukman', $factory->render('home', ['name' => 'Lukman']));
    }

    public function testMissingPartialThrowsViewNotFoundException(): void
    {
        $this->writeFile('home.php', '<?php echo $include("partials.missing"); ?>');

        $this->expectException(ViewNotFoundException::class);

        $this->factory()->render('home');
    }

    public function testIncludedPartialDoesNotOverrideParentLayout(): void
    {
        $this->writeFile('layout.php', 'Layout: <?php echo $section("content"); ?>');
        $this->writeFile('other-layout.php', 'Wrong');
        $this->writeFile('home.php', '<?php $extend("layout"); $start("content"); ?><?php echo $include("partials.card"); ?><?php $end(); ?>');
        $this->writeFile('partials/card.php', '<?php $extend("other-layout"); ?>Card');

        $this->assertSame('Layout: Card', $this->factory()->render('home'));
    }

    private function factory(): ViewFactory
    {
        return new ViewFactory(new FileViewFinder([$this->tempDir]), new PhpEngine());
    }

    private function makeDirectory(string $path): string
    {
        $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $path;
        mkdir($fullPath, 0777, true);

        return $fullPath;
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
