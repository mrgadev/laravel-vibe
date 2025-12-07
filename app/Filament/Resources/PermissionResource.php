<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (Permission $permission): bool => $permission->is_system)
                    ->label('Permission Name')
                    ->placeholder('e.g., manage_users'),

                Forms\Components\TextInput::make('resource')
                    ->required()
                    ->disabled(fn (Permission $permission): bool => $permission->is_system)
                    ->label('Resource')
                    ->placeholder('e.g., users'),

                Forms\Components\TextInput::make('action')
                    ->required()
                    ->disabled(fn (Permission $permission): bool => $permission->is_system)
                    ->label('Action')
                    ->placeholder('e.g., manage'),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Brief description of this permission...'),

                Forms\Components\Checkbox::make('is_system')
                    ->label('System Permission')
                    ->disabled()
                    ->helperText('System permissions cannot be modified or deleted'),

                Forms\Components\Repeater::make('roles')
                    ->label('Assigned to Roles')
                    ->relationship('roles')
                    ->simple(
                        Forms\Components\Select::make('role_id')
                            ->options(\App\Models\Role::where('is_system', false)->pluck('display_name', 'id'))
                            ->searchable()
                    )
                    ->collapsed()
                    ->collapsible()
                    ->addActionLabel('Add Role')
                    ->disabled(fn (Permission $permission): bool => $permission->is_system),
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
                    ->color(fn (Permission $record): string => $record->is_system ? 'warning' : 'primary'),

                Tables\Columns\TextColumn::make('resource')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
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
                Tables\Filters\SelectFilter::make('resource')
                    ->options(
                        Permission::distinct()
                            ->pluck('resource', 'resource')
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'create' => 'Create',
                        'read' => 'Read',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'manage' => 'Manage',
                        'access' => 'Access',
                    ]),
                Tables\Filters\Filter::make('system')
                    ->query(fn (Builder $query): Builder => $query->where('is_system', true))
                    ->label('System Permissions'),
                Tables\Filters\Filter::make('custom')
                    ->query(fn (Builder $query): Builder => $query->where('is_system', false))
                    ->label('Custom Permissions'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn (Permission $permission): bool => $permission->is_system),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Permission $permission): bool => $permission->is_system),
                Tables\Actions\ForceDeleteAction::make()
                    ->disabled(fn (Permission $permission): bool => $permission->is_system),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('resource', 'asc')
            ->defaultSort('action', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
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