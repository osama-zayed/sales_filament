<?php

namespace App\Filament\Resources\SupplyDetailsResource\Pages;

use App\Filament\Resources\SupplyDetailsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplyDetails extends EditRecord
{
    protected static string $resource = SupplyDetailsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
