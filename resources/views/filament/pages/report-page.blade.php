<x-filament-panels::page>
    <x-filament-panels::form wire:submit="generateReport">
        {{ $this->form }}
        
        <x-filament::button type="submit" class="mt-4">
            Generar Reporte Excel
        </x-filament::button>
    </x-filament-panels::form>

    <x-filament::section class="mt-8">
        <x-slot name="heading">
            Reportes Recientes
        </x-slot>

        <div class="space-y-2">
            @php
                $recentFiles = collect(Storage::files('public'))
                    ->filter(fn($file) => str_contains($file, 'reporte_salud_mental'))
                    ->sortDesc()
                    ->take(5);
            @endphp
            
            @forelse($recentFiles as $file)
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="text-sm">{{ basename($file) }}</span>
                    <a href="{{ Storage::url(str_replace('public/', '', $file)) }}" 
                       class="text-primary-600 hover:text-primary-500 text-sm"
                       download>
                        Descargar
                    </a>
                </div>
            @empty
                <p class="text-gray-500 text-sm">No hay reportes generados aún.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>