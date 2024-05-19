<?php

namespace App\Filament\Resources\ExchangeDetailsResource\Pages;

use App\Filament\Resources\ExchangeDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExchangeDetails extends ViewRecord
{
    protected static string $resource = ExchangeDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
