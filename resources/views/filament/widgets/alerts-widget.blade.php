// ================================
// BLADE VIEW PARA EL WIDGET DE ALERTAS
// ================================

// resources/views/filament/widgets/alerts-widget.blade.php
@php
    use App\Filament\Resources\MonthlyFollowupResource;
    use App\Filament\Resources\PatientResource;
    use App\Filament\Resources\SuicideAttemptResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bell class="w-5 h-5 text-primary-600" />
                Alertas del Sistema
            </div>
        </x-slot>

        <div class="space-y-3">
            @forelse($alerts as $alert)
                <div class="flex items-start gap-3 p-3 rounded-lg border {{ $this->getAlertStyles($alert['type']) }}">
                    <div class="flex-shrink-0">
                        @svg($alert['icon'], 'w-5 h-5 ' . $this->getIconStyles($alert['type']))
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-sm {{ $this->getTitleStyles($alert['type']) }}">
                            {{ $alert['title'] }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $alert['message'] }}
                        </p>
                    </div>
                    
                    <div class="flex-shrink-0">
                        <a href="{{ $alert['action_url'] }}" 
                           class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md {{ $this->getButtonStyles($alert['type']) }}">
                            {{ $alert['action_label'] }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <x-heroicon-o-check-circle class="w-12 h-12 text-green-400 mx-auto mb-4" />
                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">
                        ¡Todo al día!
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No hay alertas pendientes en este momento.
                    </p>
                </div>
            @endforelse
        </div>

        @if(count($alerts) > 0)
            <x-slot name="headerEnd">
                <x-filament::badge color="primary">
                    {{ count($alerts) }} {{ Str::plural('alerta', count($alerts)) }}
                </x-filament::badge>
            </x-slot>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

@php
    function getAlertStyles($type) {
        return match ($type) {
            'danger' => 'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800',
            'warning' => 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950 dark:border-yellow-800',
            'info' => 'bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800',
            'success' => 'bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800',
            default => 'bg-gray-50 border-gray-200 dark:bg-gray-950 dark:border-gray-800',
        };
    }

    function getIconStyles($type) {
        return match ($type) {
            'danger' => 'text-red-500',
            'warning' => 'text-yellow-500',
            'info' => 'text-blue-500',
            'success' => 'text-green-500',
            default => 'text-gray-500',
        };
    }

    function getTitleStyles($type) {
        return match ($type) {
            'danger' => 'text-red-800 dark:text-red-200',
            'warning' => 'text-yellow-800 dark:text-yellow-200',
            'info' => 'text-blue-800 dark:text-blue-200',
            'success' => 'text-green-800 dark:text-green-200',
            default => 'text-gray-800 dark:text-gray-200',
        };
    }

    function getButtonStyles($type) {
        return match ($type) {
            'danger' => 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-800 dark:text-red-100',
            'warning' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-800 dark:text-yellow-100',
            'info' => 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-800 dark:text-blue-100',
            'success' => 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-800 dark:text-green-100',
            default => 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100',
        };
    }
@endphp