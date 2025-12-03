<?php

namespace Filament\Support\Concerns;

use Closure;
use Filament\Support\Enums\IconPosition;

trait HasIconPosition
{
    protected IconPosition|string|Closure|null $iconPosition = null;

    public function iconPosition(IconPosition|string|Closure|null $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconPosition(): IconPosition|string|null
    {
        return $this->evaluate($this->iconPosition) ?? IconPosition::Before;
    }
}
