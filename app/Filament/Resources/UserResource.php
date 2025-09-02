<?php

// ================================
// USER RESOURCE (PARA ADMINS)
// ================================

// app/Filament/Resources/UserResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 1;

    // ✅ Solo usuarios con permiso manage_users pueden ver este recurso
    // public static function canViewAny(): bool
    // {
    //     return auth()->user()->can('manage_users');
    // }

    // public static function canCreate(): bool
    // {
    //     return auth()->user()->can('manage_users');
    // }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->can('manage_users');
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre Completo')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->options(function () {
                        $roles = Role::all()->pluck('name', 'name');

                        // ✅ Solo super_admin puede asignar rol super_admin
                        if (!auth()->user()->hasRole('super_admin')) {
                            unset($roles['super_admin']);
                        }

                        return $roles;
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Rol'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn($record) =>
                        // ✅ No puede eliminar su propia cuenta
                        $record->id !== auth()->id() &&
                            // ✅ Solo super_admin puede eliminar otros super_admin
                            (!$record->hasRole('super_admin') || auth()->user()->hasRole('super_admin'))
                    ),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ✅ Super admin puede ver todos los usuarios
        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }

        // ✅ Admin no puede ver otros super_admin
        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'super_admin');
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        if (!auth()->check()) return false;

        return auth()->user()->hasAnyRole(['super_admin', 'admin']) &&
            auth()->user()->can('manage_users');
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}
