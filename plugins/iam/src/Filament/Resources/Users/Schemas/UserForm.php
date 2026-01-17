<?php

namespace Sekeco\Iam\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->label(__('filament-panels::auth/pages/edit-profile.form.name.label'))
                        ->required()
                        ->maxLength(255)
                        ->autofocus(),
                    TextInput::make('email')
                        ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->live(debounce: 500),
                    DateTimePicker::make('email_verified_at'),
                    TextInput::make('password')
                        ->label(__('filament-panels::auth/pages/edit-profile.form.password.label'))
                        ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password.validation_attribute'))
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->rule(Password::default())
                        ->showAllValidationMessages()
                        ->autocomplete('new-password')
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                        ->live(debounce: 500)
                        ->same('passwordConfirmation'),
                    TextInput::make('passwordConfirmation')
                        ->label(__('filament-panels::auth/pages/edit-profile.form.password_confirmation.label'))
                        ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password_confirmation.validation_attribute'))
                        ->password()
                        ->autocomplete('new-password')
                        ->revealable(filament()->arePasswordsRevealable())
                        ->required()
                        ->visible(fn (Get $get): bool => filled($get('password')))
                        ->dehydrated(false),
                    CheckboxList::make('roles')
                        ->relationship('roles', 'name')
                        ->columns(2)
                        ->searchable()
                        ->bulkToggleable()
                        ->gridDirection('row'),
                ])
                    ->columns(2)
                    ->columnSpan(2),
                FileUpload::make('avatar')
                    ->label('Avatar')
                    ->image()
                    ->maxSize(1024)
                    ->directory('avatars')
                    ->image()
                    ->imageEditor()
                    ->imageAspectRatio('1:1')
                    ->automaticallyOpenImageEditorForAspectRatio(),
            ]);
    }
}
