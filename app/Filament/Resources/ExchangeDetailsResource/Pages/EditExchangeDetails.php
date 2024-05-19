<?php

namespace App\Filament\Resources\ExchangeDetailsResource\Pages;

use App\Filament\Resources\ExchangeDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExchangeDetails extends EditRecord
{
    protected static string $resource = ExchangeDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
