<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroceryStatusUpdateResource\Pages;
use App\Filament\Resources\GroceryStatusUpdateResource\RelationManagers;
use App\Models\GroceryStatusUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GroceryStatusUpdateResource extends Resource
{
    protected static ?string $model = GroceryStatusUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Grocery Status';
    protected static ?string $modelLabel = 'Grocery Status Update';
    protected static ?string $pluralModelLabel = 'Grocery Status Updates';
    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grocery Status Information')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('week_start_date')
                            ->label('Week Start Date (Monday)')
                            ->required()
                            ->native(false)
                            ->displayFormat('M j, Y')
                            ->helperText('Select any date in the week - it will be adjusted to Monday'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'needs_attention' => 'Needs Attention',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Textarea::make('items_needed')
                            ->label('Items Needed')
                            ->rows(3)
                            ->placeholder('List items that are needed...')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('items_received')
                            ->label('Items Received')
                            ->rows(3)
                            ->placeholder('List items that have been received...')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Enter any additional notes...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_start_date')
                    ->label('Week')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn ($record) => 'Week of ' . $record->week_start_date->format('M j') . ' - ' . $record->week_end_date->format('M j, Y')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'needs_attention' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('items_needed')
                    ->label('Items Needed')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('items_received')
                    ->label('Items Received')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'needs_attention' => 'Needs Attention',
                    ]),
                Tables\Filters\Filter::make('week_start_date')
                    ->form([
                        Forms\Components\DatePicker::make('week_from')
                            ->label('From Week'),
                        Forms\Components\DatePicker::make('week_until')
                            ->label('Until Week'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['week_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('week_start_date', '>=', $date),
                            )
                            ->when(
                                $data['week_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('week_start_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('week_start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroceryStatusUpdates::route('/'),
            'create' => Pages\CreateGroceryStatusUpdate::route('/create'),
            'edit' => Pages\EditGroceryStatusUpdate::route('/{record}/edit'),
        ];
    }
}
