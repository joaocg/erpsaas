<?php

namespace Filament\Support\Concerns;

use Closure;
use Filament\Support\Enums\IconSize;

trait HasIconSize
{
    protected IconSize|string|Closure|null $iconSize = null;

    protected array|Closure|null $iconSizes = null;

    public function iconSize(IconSize|string|Closure|null $size): static
    {
        $this->iconSize = $size;

        return $this;
    }

    public function iconSizes(array|Closure|null $sizes): static
    {
        $this->iconSizes = $sizes;

        return $this;
    }

    public function getIconSize(): IconSize|string|null
    {
        return $this->evaluate($this->iconSize) ?? IconSize::Medium;
    }

    public function getIconSizes(?string $name = null): array|string|null
    {
        $sizes = $this->evaluate($this->iconSizes) ?? [];

        if ($name === null) {
            return $sizes;
        }

        return $sizes[$name] ?? null;
    }
}
