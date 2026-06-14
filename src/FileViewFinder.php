<?php

declare(strict_types=1);

namespace Lukman\View;

use Lukman\View\Exception\ViewNotFoundException;

class FileViewFinder
{
    /**
     * @var list<string>
     */
    private array $paths = [];

    /**
     * @var list<string>
     */
    private array $extensions = [];

    /**
     * @var array<string, list<string>>
     */
    private array $namespaces = [];

    /**
     * @param list<string> $paths
     * @param list<string> $extensions
     */
    public function __construct(array $paths = [], array $extensions = ['php'])
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    public function addPath(string $path): void
    {
        $path = $this->normalizePath($path);

        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }
    }

    public function prependPath(string $path): void
    {
        $path = $this->normalizePath($path);
        $this->paths = array_values(array_filter(
            $this->paths,
            static fn(string $existing): bool => $existing !== $path
        ));
        array_unshift($this->paths, $path);
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    /**
     * @param string|list<string> $paths
     */
    public function addNamespace(string $namespace, string|array $paths): void
    {
        $this->namespaces[$namespace] ??= [];

        foreach ((array) $paths as $path) {
            $path = $this->normalizePath($path);

            if (!in_array($path, $this->namespaces[$namespace], true)) {
                $this->namespaces[$namespace][] = $path;
            }
        }
    }

    /**
     * @param string|list<string> $paths
     */
    public function prependNamespace(string $namespace, string|array $paths): void
    {
        $this->namespaces[$namespace] ??= [];

        foreach (array_reverse((array) $paths) as $path) {
            $path = $this->normalizePath($path);
            $this->namespaces[$namespace] = array_values(array_filter(
                $this->namespaces[$namespace],
                static fn(string $existing): bool => $existing !== $path
            ));
            array_unshift($this->namespaces[$namespace], $path);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function addExtension(string $extension): void
    {
        $extension = $this->normalizeExtension($extension);

        if (!in_array($extension, $this->extensions, true)) {
            $this->extensions[] = $extension;
        }
    }

    /**
     * @return list<string>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    public function find(string $view): string
    {
        if ($this->hasNamespace($view)) {
            return $this->findNamespacedView($view);
        }

        $path = $this->findInPaths($view, $this->paths);
        if ($path !== null) {
            return $path;
        }

        throw new ViewNotFoundException("View [{$view}] not found.");
    }

    public function exists(string $view): bool
    {
        try {
            if ($this->hasNamespace($view)) {
                return $this->findNamespacedView($view) !== null;
            }

            return $this->findInPaths($view, $this->paths) !== null;
        } catch (ViewNotFoundException) {
            return false;
        }
    }

    /**
     * @param list<string> $paths
     */
    private function findInPaths(string $view, array $paths): ?string
    {
        $viewPath = str_replace('.', DIRECTORY_SEPARATOR, $view);

        foreach ($paths as $path) {
            foreach ($this->extensions as $extension) {
                $file = $path . DIRECTORY_SEPARATOR . $viewPath . '.' . $extension;

                if (is_file($file)) {
                    return $file;
                }
            }
        }

        return null;
    }

    private function findNamespacedView(string $view): string
    {
        [$namespace, $name] = explode('::', $view, 2);

        if ($namespace === '' || $name === '') {
            throw new ViewNotFoundException("Invalid namespaced view [{$view}].");
        }

        if (!isset($this->namespaces[$namespace])) {
            throw new ViewNotFoundException("Namespace [{$namespace}] is not registered.");
        }

        $path = $this->findInPaths($name, $this->namespaces[$namespace]);
        if ($path !== null) {
            return $path;
        }

        throw new ViewNotFoundException("View [{$name}] not found in namespace [{$namespace}].");
    }

    private function hasNamespace(string $view): bool
    {
        return str_contains($view, '::');
    }

    private function normalizePath(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR . '/\\');
    }

    private function normalizeExtension(string $extension): string
    {
        return ltrim($extension, '.');
    }
}
