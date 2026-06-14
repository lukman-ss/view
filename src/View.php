<?php

declare(strict_types=1);

namespace Lukman\View;

use Throwable;

class View
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private string $name,
        private string $path,
        private array $data,
        private PhpEngine $engine
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);

            return $this;
        }

        $this->data[$key] = $value;

        return $this;
    }

    public function render(): string
    {
        return $this->engine->render($this->path, $this->data);
    }

    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Throwable) {
            return '';
        }
    }
}
