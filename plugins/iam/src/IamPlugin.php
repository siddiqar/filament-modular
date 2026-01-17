<?php

namespace Sekeco\Iam;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Sekeco\Iam\Filament\Pages\Profile;
use Sekeco\Iam\Filament\Pages\Registration;

class IamPlugin implements Plugin
{
    public function getId(): string
    {
        return 'iam';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->login()
            ->registration(Registration::class)
            ->profile(page: Profile::class, isSimple: false)
            ->emailVerification()
            ->passwordReset()
            ->discoverResources(
                in: __DIR__.'/Filament/Resources',
                for: 'Sekeco\\Iam\\Filament\\Resources',
            )
            ->discoverPages(
                in: __DIR__.'/Filament/Pages',
                for: 'Sekeco\\Iam\\Filament\\Pages',
            )
            ->discoverWidgets(
                in: __DIR__.'/Filament/Widgets',
                for: 'Sekeco\\Iam\\Filament\\Widgets',
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
