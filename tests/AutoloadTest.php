<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewException;
use Lukman\View\Exception\ViewNotFoundException;
use Lukman\View\Escaper;
use Lukman\View\FileViewFinder;
use Lukman\View\PhpEngine;
use Lukman\View\View;
use Lukman\View\ViewContext;
use Lukman\View\ViewFactory;
use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase
{
    public function testClassesCanBeAutoloaded(): void
    {
        $this->assertTrue(class_exists(View::class));
        $this->assertTrue(class_exists(ViewContext::class));
        $this->assertTrue(class_exists(ViewFactory::class));
        $this->assertTrue(class_exists(Escaper::class));
        $this->assertTrue(class_exists(FileViewFinder::class));
        $this->assertTrue(class_exists(PhpEngine::class));
        $this->assertTrue(class_exists(ViewException::class));
        $this->assertTrue(class_exists(ViewNotFoundException::class));
    }
}
