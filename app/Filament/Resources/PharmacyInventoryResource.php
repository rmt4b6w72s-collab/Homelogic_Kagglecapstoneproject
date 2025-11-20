<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PharmacyInventoryResource\Pages;
use App\Filament\Resources\PharmacyInventoryResource\RelationManagers;
use App\Models\PharmacyInventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PharmacyInventoryResource extends Resource
{
    protected static ?string $model = PharmacyInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Pharmacy Inventory';
    protected static ?string $modelLabel = 'Inventory Item';
    protected static ?string $pluralModelLabel = 'Pharmacy Inventory';
    protected static ?string $navigationGroup = 'Pharmacy Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inventory Information')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('drug_id', null)),
                        
                        Forms\Components\Select::make('drug_id')
                            ->label('Drug')
                            ->relationship('drug', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('generic_name')
                                    ->maxLength(255),
                                Forms\Components\Select::make('dosage_form')
                                    ->options([
                                        'tablet' => 'Tablet',
                                        'capsule' => 'Capsule',
                                        'liquid' => 'Liquid',
                                        'injection' => 'Injection',
                                        'cream' => 'Cream',
                                        'ointment' => 'Ointment',
                                    ]),
                            ]),
                        
                        Forms\Components\TextInput::make('quantity')
                            ->label('Current Quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Current stock quantity'),
                        
                        Forms\Components\TextInput::make('minimum_stock_level')
                            ->label('Minimum Stock Level')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Alert when stock falls below this level'),
                        
                        Forms\Components\TextInput::make('maximum_stock_level')
                            ->label('Maximum Stock Level')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Recommended maximum stock level'),
                        
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost ($)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Average unit cost'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Storage & Location')
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('Storage Location')
                            ->maxLength(255)
                            ->placeholder('e.g., Room A, Shelf 3, Refrigerator')
                            ->helperText('Location of this item in the facility'),
                        
                        Forms\Components\Toggle::make('requires_refrigeration')
                            ->label('Requires Refrigeration')
                            ->default(false)
                            ->helperText('Check if this medication must be refrigerated'),
                        
                        Forms\Components\Toggle::make('is_controlled_substance')
                            ->label('Controlled Substance')
                            ->default(false)
                            ->helperText('Check if this is a controlled substance'),
                        
                        Forms\Components\DatePicker::make('last_received_date')
                            ->label('Last Received Date')
                            ->displayFormat('M d, Y')
                            ->helperText('Date when stock was last received'),
                        
                        Forms\Components\DatePicker::make('last_dispensed_date')
                            ->label('Last Dispensed Date')
                            ->displayFormat('M d, Y')
                            ->helperText('Date when stock was last dispensed'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('storage_notes')
                            ->label('Storage Notes')
                            ->rows(3)
                            ->placeholder('Special storage instructions or notes...')
                            ->columnSpanFull(),
                    ]),
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
                
                Tables\Columns\TextColumn::make('drug.name')
                    ->label('Drug Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('drug.strength')
                    ->label('Strength')
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match ($record->stock_status) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        'overstock' => 'info',
                        default => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('minimum_stock_level')
                    ->label('Min Level')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'overstock' => 'Overstock',
                        default => 'In Stock',
                    })
                    ->color(fn ($state) => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        'overstock' => 'info',
                        default => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->placeholder('N/A'),
                
                Tables\Columns\IconColumn::make('requires_refrigeration')
                    ->label('Refrigerated')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'in_stock' => 'In Stock',
                        'overstock' => 'Overstock',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'out_of_stock') {
                            return $query->outOfStock();
                        }
                        if ($state['value'] === 'low_stock') {
                            return $query->lowStock();
                        }
                        return $query;
                    }),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('branch_id');
    }

    public static function getRelations(): array
    {
        return [
            // StockLotsRelationManager will be added later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPharmacyInventories::route('/'),
            'create' => Pages\CreatePharmacyInventory::route('/create'),
            'view' => Pages\ViewPharmacyInventory::route('/{record}'),
            'edit' => Pages\EditPharmacyInventory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
