<?php

namespace Filament\Support\Concerns;

trait HasIconPosition
{
    /*
     * Filament v3 already ships icon position handling inside the HasIcon trait.
     * This compatibility shim only needs to exist so that packages targeting
     * newer Filament versions (where the concern was split out) can keep using
     * the trait name without causing method collisions with HasIcon.
     */
}
