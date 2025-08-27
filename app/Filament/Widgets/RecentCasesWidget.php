<?php

// namespace App\Filament\Widgets;

// use Filament\Tables;
// use Filament\Tables\Table;
// use Filament\Widgets\TableWidget as BaseWidget;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Collection;

// class RecentCasesWidget extends BaseWidget
// {
//     protected static ?int $sort = 4;
//     protected int | string | array $columnSpan = 'full';

//     public function getTableQuery(): Collection
//     {
//         $mentalDisorders = \App\Models\MentalDisorder::with('patient')
//             ->where('status', 'active')
//             ->latest('admission_date')
//             ->take(3)
//             ->get()
//             ->map(function ($item) {
//                 return [
//                     'type' => 'Trastorno Mental',
//                     'patient' => $item->patient->full_name,
//                     'document' => $item->patient->document_number,
//                     'date' => $item->admission_date,
//                     'description' => $item->diagnosis_description,
//                     'status' => $item->status,
//                 ];
//             });

//         $suicideAttempts = \App\Models\SuicideAttempt::with('patient')
//             ->where('status', 'active')
//             ->latest('event_date')
//             ->take(3)
//             ->get()
//             ->map(function ($item) {
//                 return [
//                     'type' => 'Intento Suicidio',
//                     'patient' => $item->patient->full_name,
//                     'document' => $item->patient->document_number,
//                     'date' => $item->event_date,
//                     'description' => $item->trigger_factor,
//                     'status' => $item->status,
//                 ];
//             });

//         $substanceConsumptions = \App\Models\SubstanceConsumption::with('patient')
//             ->whereIn('status', ['active', 'in_treatment'])
//             ->latest('admission_date')
//             ->take(3)
//             ->get()
//             ->map(function ($item) {
//                 return [
//                     'type' => 'Consumo SPA',
//                     'patient' => $item->patient->full_name,
//                     'document' => $item->patient->document_number,
//                     'date' => $item->admission_date,
//                     'description' => implode(', ', $item->substances_used ?? []),
//                     'status' => $item->status,
//                 ];
//             });

//         return collect()
//             ->merge($mentalDisorders)
//             ->merge($suicideAttempts)
//             ->merge($substanceConsumptions)
//             ->sortByDesc('date')
//             ->take(10);
//     }

//     protected function getTableColumns(): array
//     {
//         return [
//             Tables\Columns\TextColumn::make('type')
//                 ->label('Tipo')
//                 ->badge()
//                 ->color(fn (string $state): string => match ($state) {
//                     'Trastorno Mental' => 'primary',
//                     'Intento Suicidio' => 'danger',
//                     'Consumo SPA' => 'warning',
//                     default => 'gray',
//                 }),
            
//             Tables\Columns\TextColumn::make('patient')
//                 ->label('Paciente')
//                 ->searchable(),
            
//             Tables\Columns\TextColumn::make('document')
//                 ->label('Documento')
//                 ->searchable(),
            
//             Tables\Columns\TextColumn::make('date')
//                 ->label('Fecha')
//                 ->dateTime('d/m/Y H:i')
//                 ->sortable(),
            
//             Tables\Columns\TextColumn::make('description')
//                 ->label('Descripción')
//                 ->limit(50),
            
//             Tables\Columns\TextColumn::make('status')
//                 ->label('Estado')
//                 ->badge()
//                 ->color(fn (string $state): string => match ($state) {
//                     'active' => 'success',
//                     'in_treatment' => 'info',
//                     default => 'gray',
//                 }),
//         ];
//     }

//     protected function getTableHeading(): string
//     {
//         return 'Casos Recientes';
//     }

//     protected function getTablePagination(): ?int
//     {
//         return 10;
//     }
// }