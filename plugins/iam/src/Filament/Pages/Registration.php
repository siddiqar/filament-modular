<?php

namespace Sekeco\Iam\Filament\Pages;

use Filament\Auth\Pages\Register;
use Filament\Schemas\Schema;

class Registration extends Register
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
