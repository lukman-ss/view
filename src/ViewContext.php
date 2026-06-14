<?php

declare(strict_types=1);

namespace Lukman\View;

use Lukman\View\Exception\ViewException;

class ViewContext
{
    /**
     * @var array<string, string>
     */
    private array $sections = [];

    private ?string $activeSection = null;

    private ?string $layout = null;

    public function start(string $name): void
    {
        if ($this->activeSection !== null) {
            throw new ViewException("Section [{$this->activeSection}] is already active.");
        }

        $this->activeSection = $name;
        ob_start();
    }

    public function end(): void
    {
        if ($this->activeSection === null) {
            throw new ViewException('No active section to end.');
        }

        $this->sections[$this->activeSection] = ob_get_clean() ?: '';
        $this->activeSection = null;
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function pullLayout(): ?string
    {
        $layout = $this->layout;
        $this->layout = null;

        return $layout;
    }

    public function currentLayout(): ?string
    {
        return $this->layout;
    }

    public function restoreLayout(?string $layout): void
    {
        $this->layout = $layout;
    }

    public function cleanupActiveSection(): void
    {
        if ($this->activeSection !== null && ob_get_level() > 0) {
            ob_end_clean();
            $this->activeSection = null;
        }
    }
}
