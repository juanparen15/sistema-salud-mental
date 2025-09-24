<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RolePermissionResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Roles y Permisos';
    protected static ?string $modelLabel = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('roles_view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('roles_create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('roles_edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        // No permitir eliminar roles del sistema
        if (in_array($record->name, ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'])) {
            return false;
        }
        return auth()->user()?->can('roles_delete') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('roles_view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Rol')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Rol')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Nombre interno del rol (ej: psychologist)')
                            ->disabled(fn($record) => $record && in_array($record->name, ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'])),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Nombre para Mostrar')
                            ->maxLength(255)
                            ->helperText('Nombre amigable (ej: Psicólogo)')
                            ->default(fn($get) => ucfirst(str_replace('_', ' ', $get('name')))),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->helperText('Describe las responsabilidades de este rol'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color del Rol')
                            ->default('#6366f1'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permisos')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permisos Asignados')
                            ->relationship('permissions', 'id')
                            ->options(function () {
                                return Permission::all()->mapWithKeys(function ($permission) {
                                    return [$permission->id => $permission->display_name ?: $permission->name];
                                })->toArray();
                            })
                            ->descriptions(function () {
                                return Permission::all()->mapWithKeys(function ($permission) {
                                    return [$permission->id => $permission->description];
                                })->filter()->toArray();
                            })
                            ->columns(3)
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => match ($record->name) {
                        'super_admin' => 'danger',
                        'admin' => 'primary',
                        'coordinator' => 'success',
                        'psychologist' => 'info',
                        'social_worker' => 'warning',
                        'assistant' => 'secondary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nombre para Mostrar')
                    ->searchable()
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->description),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('permissions')
                    ->relationship('permissions', 'name')
                    ->label('Por Permiso'),

                Tables\Filters\Filter::make('with_users')
                    ->label('Con Usuarios')
                    ->query(fn($query) => $query->whereHas('users')),
            ])
            ->actions([
                Tables\Actions\Action::make('manage_permissions')
                    ->label('Gestionar Permisos')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->form([
                        Forms\Components\Tabs::make('permission_tabs')
                            ->tabs(function () {
                                $permissions = Permission::all()->groupBy(function ($permission) {
                                    $parts = explode('_', $permission->name);
                                    $modules = [
                                        'dashboard' => 'Dashboard',
                                        'patients' => 'Pacientes',
                                        'followups' => 'Seguimientos',
                                        'reports' => 'Reportes',
                                        'users' => 'Usuarios',
                                        'roles' => 'Roles',
                                        'permissions' => 'Roles',
                                        'system' => 'Sistema',
                                        'bulk' => 'Lote',
                                        'config' => 'Config'
                                    ];
                                    
                                    $module = $parts[0];
                                    return $modules[$module] ?? ucfirst($module);
                                });

                                $tabs = [];
                                foreach ($permissions as $moduleName => $perms) {
                                    $tabs[] = Forms\Components\Tabs\Tab::make($moduleName)
                                        ->schema([
                                            Forms\Components\CheckboxList::make(Str::slug($moduleName) . '_permissions')
                                                ->label("Permisos de {$moduleName}")
                                                ->options(
                                                    $perms->mapWithKeys(function ($permission) {
                                                        return [$permission->id => $permission->display_name ?: $permission->name];
                                                    })->toArray()
                                                )
                                                ->descriptions(
                                                    $perms->mapWithKeys(function ($permission) {
                                                        return [$permission->id => $permission->description];
                                                    })->filter()->toArray()
                                                )
                                                ->columns(2)
                                        ]);
                                }
                                
                                return $tabs;
                            })
                    ])
                    ->fillForm(function ($record) {
                        $permissions = $record->permissions->pluck('id')->toArray();
                        $data = [];

                        $groupedPermissions = Permission::all()->groupBy(function ($permission) {
                            $parts = explode('_', $permission->name);
                            $modules = [
                                'dashboard' => 'Dashboard',
                                'patients' => 'Pacientes',
                                'followups' => 'Seguimientos',
                                'reports' => 'Reportes',
                                'users' => 'Usuarios',
                                'roles' => 'Roles',
                                'permissions' => 'Roles',
                                'system' => 'Sistema',
                                'bulk' => 'Lote',
                                'config' => 'Config'
                            ];
                            
                            $module = $parts[0];
                            return $modules[$module] ?? ucfirst($module);
                        });

                        foreach ($groupedPermissions as $moduleName => $perms) {
                            $modulePermissions = $perms->pluck('id')->intersect($permissions);
                            $data[Str::slug($moduleName) . '_permissions'] = $modulePermissions->toArray();
                        }

                        return $data;
                    })
                    ->action(function ($record, array $data) {
                        try {
                            $allPermissions = [];

                            foreach ($data as $key => $permissions) {
                                if (str_ends_with($key, '_permissions') && is_array($permissions)) {
                                    $allPermissions = array_merge($allPermissions, $permissions);
                                }
                            }

                            $record->syncPermissions($allPermissions);

                            Notification::make()
                                ->title('Permisos actualizados')
                                ->body("Se actualizaron los permisos del rol {$record->display_name}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al actualizar permisos')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('7xl')
                    ->visible(fn() => auth()->user()->can('permissions_manage')),

                Tables\Actions\Action::make('view_users')
                    ->label('Ver Usuarios')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->url(fn($record) => route('filament.admin.resources.users.index', [
                        'tableFilters' => [
                            'roles' => ['value' => $record->name]
                        ]
                    ]))
                    ->visible(fn() => auth()->user()->can('users_view')),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->can('roles_edit')),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => auth()->user()->can('roles_delete') && 
                        !in_array($record->name, ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant']))
                    ->requiresConfirmation()
                    ->modalDescription('¿Seguro? Los usuarios con este rol perderán sus permisos.')
                    ->before(function ($record) {
                        if ($record->users()->count() > 0) {
                            Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Este rol tiene usuarios asignados.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_permissions')
                        ->label('Asignar Permisos')
                        ->icon('heroicon-o-key')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('permissions')
                                ->label('Permisos')
                                ->multiple()
                                ->options(Permission::all()->pluck('display_name', 'name'))
                                ->searchable()
                                ->required(),
                            Forms\Components\Radio::make('action')
                                ->label('Acción')
                                ->options([
                                    'add' => 'Agregar permisos',
                                    'remove' => 'Remover permisos',
                                    'sync' => 'Sincronizar (reemplazar)',
                                ])
                                ->default('add')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $role) {
                                try {
                                    switch ($data['action']) {
                                        case 'add':
                                            $role->givePermissionTo($data['permissions']);
                                            break;
                                        case 'remove':
                                            $role->revokePermissionTo($data['permissions']);
                                            break;
                                        case 'sync':
                                            $role->syncPermissions($data['permissions']);
                                            break;
                                    }
                                    $count++;
                                } catch (\Exception $e) {
                                    Log::error("Error updating permissions for role {$role->name}: " . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('Permisos actualizados')
                                ->body("Se actualizaron {$count} roles.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => auth()->user()->can('permissions_manage')),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRolePermissions::route('/'),
            'create' => Pages\CreateRolePermission::route('/create'),
            'edit' => Pages\EditRolePermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Role::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}