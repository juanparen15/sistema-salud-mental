<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Importación Masiva de Pacientes
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Importa múltiples pacientes desde un archivo Excel sin crear duplicados
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        ¿Cómo funciona la importación sin duplicados?
                    </h4>
                    <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        <p>El sistema utiliza el <strong>número de identificación</strong> como clave única:</p>
                        <ul class="mt-2 space-y-1 list-disc list-inside">
                            <li>Si el paciente <strong>no existe</strong>: se crea un nuevo registro</li>
                            <li>Si el paciente <strong>ya existe</strong>: se actualizan sus datos</li>
                            <li>Los <strong>seguimientos</strong> se agregan sin duplicar fechas existentes</li>
                            <li>Puedes importar el <strong>mismo archivo múltiples veces</strong> conforme se actualice</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{ $this->form }}

        <div class="flex justify-between items-center">
            <div class="flex space-x-3">
                {{ $this->importAction }}
                {{ $this->downloadTemplateAction }}
            </div>
            
            <div class="text-sm text-gray-500">
                <p>💡 <strong>Consejo:</strong> Descarga la plantilla para ver el formato correcto</p>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Consideraciones Importantes
                    </h4>
                    <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        <ul class="space-y-1 list-disc list-inside">
                            <li>Asegúrate de que los <strong>números de identificación</strong> sean correctos y únicos</li>
                            <li>Las fechas pueden estar en formato: DD/MM/YYYY, MM/DD/YYYY, o YYYY-MM-DD</li>
                            <li>Para campos booleanos usa: "sí"/"no", "true"/"false", o "1"/"0"</li>
                            <li>El género acepta: "M", "F", "Masculino", "Femenino", "Hombre", "Mujer"</li>
                            <li>Los archivos grandes pueden tomar varios minutos en procesar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>