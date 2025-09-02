<?php

namespace App\Filament\Resources\SubstanceConsumptionResource\Pages;

use App\Filament\Resources\SubstanceConsumptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubstanceConsumption extends CreateRecord
{
    protected static string $resource = SubstanceConsumptionResource::class;

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