<?php

namespace App\Filament\User\Clusters;

use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;

class Account extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationLabel = null;

    protected static ?string $clusterBreadcrumb = null;

    public static function getNavigationUrl(): string
    {
        return static::getUrl(panel: 'user');
    }

    public static function getNavigationLabel(): string
    {
        return __('My Account');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('My Account');
    }

    public static function canAccess(): bool
    {
        return ! is_demo_environment();
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->visible(static::canAccess())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getNavigationUrl()),
        ];
    }
}
