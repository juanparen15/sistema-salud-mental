<?php

namespace App\Filament\Resources\MentalDisorderResource\Pages;

use App\Filament\Resources\MentalDisorderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMentalDisorder extends EditRecord
{
    protected static string $resource = MentalDisorderResource::class;

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