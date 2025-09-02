<?php

namespace App\Filament\Resources\SubstanceConsumptionResource\Pages;

use App\Filament\Resources\SubstanceConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubstanceConsumption extends EditRecord
{
    protected static string $resource = SubstanceConsumptionResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('edit_patients'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('delete_patients')),
        ];
    }
}