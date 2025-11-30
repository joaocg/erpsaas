<?php

namespace Filament\Support\Concerns;

use Filament\Support\Enums\IconSize;

trait HasIconSize
{
    protected IconSize|string|null $iconSize = null;

    public function iconSize(IconSize|string|null $size): static
    {
        $this->iconSize = $size;

        return $this;
    }

    public function getIconSize(): IconSize|string|null
    {
        return $this->evaluate($this->iconSize) ?? IconSize::Medium;
    }
}
