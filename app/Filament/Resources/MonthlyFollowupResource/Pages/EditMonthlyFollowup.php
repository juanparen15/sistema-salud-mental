<?php

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyFollowup extends EditRecord
{
    protected static string $resource = MonthlyFollowupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
