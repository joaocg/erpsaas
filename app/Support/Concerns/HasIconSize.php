<?php

namespace Filament\Support\Concerns;

/**
 * Placeholder trait to satisfy packages expecting HasIconSize alongside
 * Filament's HasIcon concern. The real icon sizing helpers are already
 * provided by Filament\Support\Concerns\HasIcon, which exposes the
 * `iconSize()` and `getIconSize()` methods. Leaving this trait empty prevents
 * method collisions when classes `use HasIcon, HasIconSize` while keeping the
 * trait available for compatibility.
 */
trait HasIconSize
{
}
