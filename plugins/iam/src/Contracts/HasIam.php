<?php

namespace Sekeco\Iam\Contracts;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * Contract for models that integrate with the IAM plugin.
 *
 * This interface extends Filament's FilamentUser contract with IAM-specific
 * requirements for panel access. Tenancy support is optional and automatically
 * enabled when configured.
 */
interface HasIam extends FilamentUser
{
    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool;
}
