<?php

declare(strict_types=1);

namespace Lukman\View;

use Lukman\View\Exception\ViewException;

class Escaper
{
    public function escape(mixed $value): string
    {
        return htmlspecialchars($this->toString($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function raw(mixed $value): string
    {
        return $this->toString($value);
    }

    private function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        throw new ViewException('Value cannot be converted to string.');
    }
}
