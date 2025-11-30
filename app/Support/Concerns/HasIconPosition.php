<?php

namespace Filament\Support\Concerns;

use Filament\Support\Enums\IconPosition;

trait HasIconPosition
{
    protected IconPosition|string|null $iconPosition = null;

    public function iconPosition(IconPosition|string|null $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconPosition(): IconPosition|string|null
    {
        return $this->evaluate($this->iconPosition) ?? IconPosition::Before;
    }
}
