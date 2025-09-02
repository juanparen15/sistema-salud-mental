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
        return auth()->user()->hasRole('admin');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('admin');
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
                            ->helperText('Nombre interno del rol (ej: psychologist)'),

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
                            ->relationship('permissions', 'name')
                            ->label('Permisos Asignados')
                            ->options(function () {
                                return Permission::all()
                                    ->groupBy(function ($permission) {
                                        return explode('_', $permission->name)[0];
                                    })
                                    ->map(function ($permissions, $category) {
                                        return $permissions->pluck('name', 'name')->toArray();
                                    })
                                    ->toArray();
                            })
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
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
                    ->searchable(),

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
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permisos del Rol')
                            ->options(function () {
                                $permissions = Permission::all()->groupBy(function ($permission) {
                                    $parts = explode('_', $permission->name);
                                    return ucfirst($parts[0]);
                                });

                                $options = [];
                                foreach ($permissions as $category => $perms) {
                                    foreach ($perms as $perm) {
                                        $options[$category][$perm->name] = ucfirst(str_replace('_', ' ', $perm->name));
                                    }
                                }
                                return $options;
                            })
                            ->columns(2)
                            ->gridDirection('row'),
                    ])
                    ->fillForm(fn($record) => [
                        'permissions' => $record->permissions->pluck('name')->toArray()
                    ])
                    ->action(function ($record, array $data) {
                        $record->syncPermissions($data['permissions']);

                        Notification::make()
                            ->title('Permisos actualizados')
                            ->body("Los permisos del rol {$record->name} han sido actualizados correctamente.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_users')
                    ->label('Ver Usuarios')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->url(fn($record) => UserResource::getUrl('index', ['tableFilters[roles][value]' => $record->name]))
                    ->visible(fn() => auth()->user()->can('manage_users')),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => !in_array($record->name, [
                        'super_admin',
                        'admin',
                        'coordinator',
                        'psychologist',
                        'social_worker',
                        'assistant'
                    ]))
                    ->requiresConfirmation()
                    ->modalDescription('¿Estás seguro? Los usuarios con este rol perderán sus permisos.')
                    ->before(function ($record) {
                        if ($record->users()->count() > 0) {
                            Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Este rol tiene usuarios asignados. Primero reasigna o elimina los usuarios.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_permissions')
                        ->label('Asignar Permisos en Lote')
                        ->icon('heroicon-o-key')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('permissions')
                                ->label('Permisos a Asignar')
                                ->multiple()
                                ->options(Permission::pluck('name', 'name'))
                                ->searchable()
                                ->required(),
                            Forms\Components\Radio::make('action')
                                ->label('Acción')
                                ->options([
                                    'add' => 'Agregar permisos',
                                    'remove' => 'Remover permisos',
                                    'sync' => 'Sincronizar (reemplazar todos)',
                                ])
                                ->default('add')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $role) {
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
                            }

                            Notification::make()
                                ->title('Permisos actualizados en lote')
                                ->body("Se actualizaron {$count} roles correctamente.")
                                ->success()
                                ->send();
                        }),
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
            'permissions' => Pages\ManagePermissions::route('/permissions'),
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
