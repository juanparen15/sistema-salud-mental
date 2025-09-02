<?php

namespace App\Filament\Resources\MentalDisorderResource\Pages;

// ================================
// MENTAL DISORDER PAGES
// ================================

use App\Filament\Resources\MentalDisorderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMentalDisorder extends CreateRecord
{
    protected static string $resource = MentalDisorderResource::class;

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