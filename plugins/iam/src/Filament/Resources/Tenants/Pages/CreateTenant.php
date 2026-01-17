<?php

namespace Sekeco\Iam\Filament\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Sekeco\Iam\Filament\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
