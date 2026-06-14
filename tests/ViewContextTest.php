<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Exception\ViewException;
use Lukman\View\ViewContext;
use PHPUnit\Framework\TestCase;

class ViewContextTest extends TestCase
{
    public function testSectionReturnsDefaultWhenMissing(): void
    {
        $context = new ViewContext();

        $this->assertSame('Default', $context->section('missing', 'Default'));
    }

    public function testStartAndEndCaptureSectionOutput(): void
    {
        $context = new ViewContext();

        $context->start('content');
        echo 'Captured';
        $context->end();

        $this->assertSame('Captured', $context->section('content'));
    }

    public function testEndWithoutStartThrowsViewException(): void
    {
        $context = new ViewContext();

        $this->expectException(ViewException::class);

        $context->end();
    }

    public function testNestedStartThrowsViewException(): void
    {
        $context = new ViewContext();

        $context->start('one');

        try {
            $this->expectException(ViewException::class);
            $context->start('two');
        } finally {
            ob_end_clean();
        }
    }

    public function testLayoutCanBePulledAndRestored(): void
    {
        $context = new ViewContext();
        $context->extend('layouts.app');

        $this->assertSame('layouts.app', $context->currentLayout());
        $this->assertSame('layouts.app', $context->pullLayout());
        $this->assertNull($context->currentLayout());

        $context->restoreLayout('layouts.app');

        $this->assertSame('layouts.app', $context->currentLayout());
    }
}
