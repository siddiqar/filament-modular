<?php

namespace Sekeco\Iam\Filament\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class Profile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
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
