<?php

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyFollowups extends ListRecords
{
    protected static string $resource = MonthlyFollowupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
