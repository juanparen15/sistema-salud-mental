<?php

namespace App\Filament\Resources\SuicideAttemptResource\Pages;

use App\Filament\Resources\SuicideAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuicideAttempt extends EditRecord
{
    protected static string $resource = SuicideAttemptResource::class;

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