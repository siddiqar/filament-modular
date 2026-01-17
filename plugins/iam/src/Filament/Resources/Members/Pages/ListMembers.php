<?php

declare(strict_types=1);

namespace Sekeco\Iam\Filament\Resources\Members\Pages;

use Filament\Resources\Pages\ListRecords;
use Sekeco\Iam\Filament\Resources\Members\MemberResource;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;
}
