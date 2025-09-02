<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('edit_patients'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('view_patients')),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('delete_patients')),
        ];
    }
}