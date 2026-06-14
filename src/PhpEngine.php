<?php

declare(strict_types=1);

namespace Lukman\View;

use Lukman\View\Exception\ViewException;
use Lukman\View\Exception\ViewNotFoundException;
use Throwable;

class PhpEngine
{
    private ?FileViewFinder $finder = null;

    public function __construct(
        private ?Escaper $escaper = null
    ) {
        $this->escaper ??= new Escaper();
    }

    public function setFinder(FileViewFinder $finder): void
    {
        $this->finder = $finder;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $path, array $data = []): string
    {
        return $this->renderWithContext($path, $data, new ViewContext());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderWithContext(string $path, array $data, ViewContext $context): string
    {
        return $this->renderFile($path, $data, $context, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderPartial(string $view, array $data, ViewContext $context): string
    {
        if ($this->finder === null) {
            throw new ViewException("Cannot include view [{$view}] without a view finder.");
        }

        $layout = $context->currentLayout();

        try {
            return $this->renderFile($this->finder->find($view), $data, $context, false);
        } finally {
            $context->restoreLayout($layout);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $path, array $data, ViewContext $context, bool $renderLayout): string
    {
        if (!is_file($path)) {
            throw new ViewNotFoundException("View file [{$path}] not found.");
        }

        $obLevel = ob_get_level();

        ob_start();

        try {
            $this->evaluate($path, $data, $context);
            $output = ob_get_clean() ?: '';

            if (!$renderLayout) {
                return $output;
            }

            $layout = $context->pullLayout();
            if ($layout === null) {
                return $output;
            }

            if ($this->finder === null) {
                throw new ViewException("Cannot render layout [{$layout}] without a view finder.");
            }

            return $this->renderWithContext($this->finder->find($layout), $data, $context);
        } catch (Throwable $exception) {
            $context->cleanupActiveSection();

            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            if ($exception instanceof ViewNotFoundException) {
                throw $exception;
            }

            throw new ViewException(
                "Error rendering view file [{$path}]: {$exception->getMessage()}",
                0,
                $exception
            );
        }
    }

    /**
     * @param array<string, mixed> $__data
     */
    private function evaluate(string $__path, array $__data, ViewContext $__context): void
    {
        $__parentData = $__data;
        $e = fn(mixed $value): string => $this->escaper->escape($value);
        $raw = fn(mixed $value): string => $this->escaper->raw($value);
        $start = function (string $name) use ($__context): void {
            $__context->start($name);
        };
        $end = function () use ($__context): void {
            $__context->end();
        };
        $section = fn(string $name, string $default = ''): string => $__context->section($name, $default);
        $extend = function (string $layout) use ($__context): void {
            $__context->extend($layout);
        };
        $include = function (string $view, array $data = []) use ($__context, $__parentData): string {
            return $this->renderPartial($view, array_merge($__parentData, $data), $__context);
        };

        extract($__data, EXTR_SKIP);

        include $__path;
    }
}
