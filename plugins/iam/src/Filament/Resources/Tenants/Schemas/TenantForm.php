<?php

namespace Sekeco\Iam\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null
                        ),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->alphaDash()
                        ->helperText('Used in URLs and must be unique.'),

                    FileUpload::make('logo')
                        ->image()
                        ->directory('tenants/logos')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->default(true)
                        ->helperText('Inactive tenants cannot be accessed.'),
                ]),
        ]);
    }
}
