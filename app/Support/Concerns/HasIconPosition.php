<?php

namespace Filament\Support\Concerns;

/**
 * This placeholder prevents trait resolution collisions when packages expect
 * the Filament-provided concern to exist alongside {@see HasIcon}.
 *
 * The real icon positioning helpers are already supplied by Filament\Support\Concerns\HasIcon,
 * which exposes the `iconPosition()` and `getIconPosition()` methods. Keeping
 * this trait empty allows classes that `use HasIcon, HasIconPosition` to load
 * without method conflicts while still honoring package expectations that the
 * trait be available.
 */
trait HasIconPosition
{
}
