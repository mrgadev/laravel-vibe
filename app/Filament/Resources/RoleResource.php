<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (Role $role): bool => $role->is_system)
                    ->label('Role Name')
                    ->placeholder('e.g., content_manager'),

                Forms\Components\TextInput::make('display_name')
                    ->label('Display Name')
                    ->placeholder('e.g., Content Manager'),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Brief description of this role...'),

                Forms\Components\Checkbox::make('is_system')
                    ->label('System Role')
                    ->disabled()
                    ->helperText('System roles cannot be modified or deleted'),

                Forms\Components\CheckboxList::make('permissions')
                    ->label('Permissions')
                    ->relationship('permissions', 'name')
                    ->searchable()
                    ->bulkToggleable()
                    ->options(function () {
                        return Permission::all()
                            ->groupBy('resource')
                            ->map(function ($permissions, $resource) {
                                return [
                                    'label' => ucfirst($resource),
                                    'options' => $permissions->pluck('name', 'id')->toArray(),
                                ];
                            })
                            ->values()
                            ->collapse();
                    })
                    ->disabled(fn (Role $role): bool => $role->is_system),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Role $record): string => $record->is_system ? 'warning' : 'primary'),

                Tables\Columns\TextColumn::make('display_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_system')
                    ->boolean()
                    ->label('System'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('system')
                    ->query(fn (Builder $query): Builder => $query->where('is_system', true))
                    ->label('System Roles'),
                Tables\Filters\Filter::make('custom')
                    ->query(fn (Builder $query): Builder => $query->where('is_system', false))
                    ->label('Custom Roles'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn (Role $role): bool => $role->is_system),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Role $role): bool => $role->is_system),
                Tables\Actions\ForceDeleteAction::make()
                    ->disabled(fn (Role $role): bool => $role->is_system),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}