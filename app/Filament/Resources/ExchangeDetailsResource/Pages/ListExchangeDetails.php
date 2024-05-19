<?php

namespace App\Filament\Resources\ExchangeDetailsResource\Pages;

use App\Filament\Resources\ExchangeDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExchangeDetails extends ListRecords
{
    protected static string $resource = ExchangeDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
