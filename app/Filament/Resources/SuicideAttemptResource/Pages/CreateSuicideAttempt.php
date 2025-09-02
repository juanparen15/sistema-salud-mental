<?php

namespace App\Filament\Resources\SuicideAttemptResource\Pages;

use App\Filament\Resources\SuicideAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSuicideAttempt extends CreateRecord
{
    protected static string $resource = SuicideAttemptResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create_patients'), 403);
        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        return $data;
    }
}
