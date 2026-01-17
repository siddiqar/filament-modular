<?php

namespace Sekeco\Iam\Filament\Resources\Tenants\Pages;

use Filament\Resources\Pages\ListRecords;
use Sekeco\Iam\Filament\Resources\Tenants\TenantResource;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;
}
