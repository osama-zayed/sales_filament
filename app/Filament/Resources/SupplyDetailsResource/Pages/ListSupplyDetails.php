<?php

namespace App\Filament\Resources\SupplyDetailsResource\Pages;

use App\Filament\Resources\SupplyDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplyDetails extends ListRecords
{
    protected static string $resource = SupplyDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
