@php
    $permissionMatrix = $this->getPermissionMatrix();
    $roles = ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'];
    $roleColors = [
        'super_admin' => 'bg-red-100 text-red-800',
        'admin' => 'bg-blue-100 text-blue-800',
        'coordinator' => 'bg-green-100 text-green-800',
        'psychologist' => 'bg-purple-100 text-purple-800',
        'social_worker' => 'bg-yellow-100 text-yellow-800',
        'assistant' => 'bg-gray-100 text-gray-800',
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Total Roles</h3>
                <p class="text-2xl font-bold text-blue-600">{{ \Spatie\Permission\Models\Role::count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Total Permisos</h3>
                <p class="text-2xl font-bold text-green-600">{{ \Spatie\Permission\Models\Permission::count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Usuarios Activos</h3>
                <p class="text-2xl font-bold text-purple-600">{{ \App\Models\User::count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Configuraciones</h3>
                <p class="text-2xl font-bold text-orange-600">{{ \App\Models\SystemSetting::count() ?? 0 }}</p>
            </div>
        </div>

        <!-- Matriz de Permisos -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Matriz de Permisos por Rol</h3>
                <p class="text-sm text-gray-600">Vista general de permisos asignados a cada rol</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permiso</th>
                            @foreach($roles as $role)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs {{ $roleColors[$role] ?? 'bg-gray-100' }}">
                                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($permissionMatrix['matrix'] as $category => $permissions)
                            <tr class="bg-gray-25">
                                <td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 bg-gray-100">
                                    <strong class="text-sm font-semibold uppercase text-gray-700">
                                        📂 {{ ucfirst($category) }}
                                    </strong>
                                </td>
                            </tr>
                            @foreach($permissions as $permissionName => $rolePermissions)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $permissionName)) }}</span>
                                        <br>
                                        <span class="text-xs text-gray-500">{{ $permissionName }}</span>
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="px-4 py-3 text-center">
                                            @if($rolePermissions[$role] ?? false)
                                                <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 rounded-full">
                                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 bg-red-100 rounded-full">
                                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resumen por Rol -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(\Spatie\Permission\Models\Role::with('permissions', 'users')->get() as $role)
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold {{ $roleColors[$role->name] ?? '' }} px-2 py-1 rounded">
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </h4>
                            <span class="text-sm text-gray-500">{{ $role->users->count() }} usuarios</span>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600 mb-3">
                            <strong>{{ $role->permissions->count() }}</strong> permisos asignados
                        </p>
                        <div class="space-y-1">
                            @foreach($role->permissions->take(5) as $permission)
                                <div class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                </div>
                            @endforeach
                            @if($role->permissions->count() > 5)
                                <div class="text-xs text-gray-500">
                                    y {{ $role->permissions->count() - 5 }} más...
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>