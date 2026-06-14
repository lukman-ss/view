<?php

declare(strict_types=1);

namespace Lukman\View\Tests;

use Lukman\View\Escaper;
use Lukman\View\Exception\ViewException;
use PHPUnit\Framework\TestCase;
use Stringable;
use stdClass;

class EscaperTest extends TestCase
{
    public function testEscapeUsesHtmlspecialcharsWithQuotesAndUtf8(): void
    {
        $escaper = new Escaper();

        $this->assertSame('&lt;a title=&quot;Lukman&#039;s&quot;&gt;', $escaper->escape('<a title="Lukman\'s">'));
    }

    public function testNullEscapesToEmptyString(): void
    {
        $escaper = new Escaper();

        $this->assertSame('', $escaper->escape(null));
    }

    public function testArrayThrowsViewException(): void
    {
        $escaper = new Escaper();

        $this->expectException(ViewException::class);

        $escaper->escape([]);
    }

    public function testObjectWithoutToStringThrowsViewException(): void
    {
        $escaper = new Escaper();

        $this->expectException(ViewException::class);

        $escaper->escape(new stdClass());
    }

    public function testObjectWithToStringCanBeEscaped(): void
    {
        $escaper = new Escaper();
        $value = new class implements Stringable {
            public function __toString(): string
            {
                return '<b>Value</b>';
            }
        };

        $this->assertSame('&lt;b&gt;Value&lt;/b&gt;', $escaper->escape($value));
    }

    public function testRawDoesNotEscapeOutput(): void
    {
        $escaper = new Escaper();

        $this->assertSame('<strong>"Raw"</strong>', $escaper->raw('<strong>"Raw"</strong>'));
    }
}
