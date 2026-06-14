<?php

declare(strict_types=1);

namespace Lukman\View;

class ViewFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $shared = [];

    public function __construct(
        private FileViewFinder $finder,
        private PhpEngine $engine
    ) {
        $this->engine->setFinder($this->finder);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(string $view, array $data = []): View
    {
        return new View(
            $view,
            $this->finder->find($view),
            array_merge($this->shared, $data),
            $this->engine
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        return $this->make($view, $data)->render();
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);

            return;
        }

        $this->shared[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        return $this->shared;
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    public function finder(): FileViewFinder
    {
        return $this->finder;
    }

    public function engine(): PhpEngine
    {
        return $this->engine;
    }
}
