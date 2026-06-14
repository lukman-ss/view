<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewNotFoundException;
use Lukman\View\FileViewFinder;
use PHPUnit\Framework\TestCase;

class FileViewFinderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lukman_view_' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testFindConvertsDotNotationToPath(): void
    {
        $this->writeFile('pages/about.php', 'first');

        $finder = new FileViewFinder([$this->tempDir]);

        $this->assertSame($this->tempDir . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'about.php', $finder->find('pages.about'));
    }

    public function testFindChecksMultiplePathsInOrder(): void
    {
        $first = $this->makeDirectory('first');
        $second = $this->makeDirectory('second');
        $this->writeFile('second/home.php', 'second');

        $finder = new FileViewFinder([$first, $second]);

        $this->assertSame($second . DIRECTORY_SEPARATOR . 'home.php', $finder->find('home'));
    }

    public function testPrependPathGivesPathPriorityAndPreventsDuplicates(): void
    {
        $first = $this->makeDirectory('first');
        $second = $this->makeDirectory('second');
        $this->writeFile('first/home.php', 'first');
        $this->writeFile('second/home.php', 'second');

        $finder = new FileViewFinder([$first, $second, $first]);
        $finder->prependPath($second);

        $this->assertSame([$second, $first], $finder->paths());
        $this->assertSame($second . DIRECTORY_SEPARATOR . 'home.php', $finder->find('home'));
    }

    public function testExtensionsAreNormalizedAndPriorityIsStable(): void
    {
        $this->writeFile('home.tpl.php', 'tpl');
        $this->writeFile('home.php', 'php');

        $finder = new FileViewFinder([$this->tempDir], ['.tpl.php', 'php', '.tpl.php']);

        $this->assertSame(['tpl.php', 'php'], $finder->extensions());
        $this->assertSame($this->tempDir . DIRECTORY_SEPARATOR . 'home.tpl.php', $finder->find('home'));
    }

    public function testExistsReturnsBooleanWithoutThrowing(): void
    {
        $finder = new FileViewFinder([$this->tempDir]);

        $this->assertFalse($finder->exists('missing'));
    }

    public function testFindThrowsViewNotFoundException(): void
    {
        $finder = new FileViewFinder([$this->tempDir]);

        $this->expectException(ViewNotFoundException::class);

        $finder->find('missing');
    }

    public function testFindParsesNamespacedViewAndNestedDotNotation(): void
    {
        $admin = $this->makeDirectory('admin');
        $this->writeFile('admin/users/profile.php', 'profile');
        $finder = new FileViewFinder();
        $finder->addNamespace('admin', $admin);

        $this->assertSame(
            $admin . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'profile.php',
            $finder->find('admin::users.profile')
        );
    }

    public function testNamespacedViewChecksMultiplePathsInOrder(): void
    {
        $first = $this->makeDirectory('theme-one');
        $second = $this->makeDirectory('theme-two');
        $this->writeFile('theme-two/dashboard.php', 'dashboard');
        $finder = new FileViewFinder();
        $finder->addNamespace('admin', [$first, $second]);

        $this->assertSame($second . DIRECTORY_SEPARATOR . 'dashboard.php', $finder->find('admin::dashboard'));
    }

    public function testPrependNamespaceGivesPriorityAndPreventsDuplicates(): void
    {
        $first = $this->makeDirectory('first-admin');
        $second = $this->makeDirectory('second-admin');
        $this->writeFile('first-admin/dashboard.php', 'first');
        $this->writeFile('second-admin/dashboard.php', 'second');
        $finder = new FileViewFinder();
        $finder->addNamespace('admin', [$first, $second, $first]);
        $finder->prependNamespace('admin', $second);

        $this->assertSame(['admin' => [$second, $first]], $finder->namespaces());
        $this->assertSame($second . DIRECTORY_SEPARATOR . 'dashboard.php', $finder->find('admin::dashboard'));
    }

    public function testMissingNamespaceThrowsViewNotFoundException(): void
    {
        $finder = new FileViewFinder();

        $this->expectException(ViewNotFoundException::class);

        $finder->find('admin::dashboard');
    }

    public function testExistsSupportsNamespacedViews(): void
    {
        $admin = $this->makeDirectory('admin-exists');
        $this->writeFile('admin-exists/dashboard.php', '');
        $finder = new FileViewFinder();
        $finder->addNamespace('admin', $admin);

        $this->assertTrue($finder->exists('admin::dashboard'));
        $this->assertFalse($finder->exists('admin::missing'));
        $this->assertFalse($finder->exists('missing::dashboard'));
    }

    public function testInvalidNamespacedViewThrowsViewNotFoundException(): void
    {
        $finder = new FileViewFinder();

        $this->expectException(ViewNotFoundException::class);

        $finder->find('admin::');
    }

    private function makeDirectory(string $path): string
    {
        $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . $path;
        mkdir($fullPath, 0777, true);

        return $fullPath;
    }

    private function writeFile(string $path, string $contents): void
    {
        $fullPath = $this->tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($fullPath, $contents);
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
