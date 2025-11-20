<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PharmacySupplierResource\Pages;
use App\Filament\Resources\PharmacySupplierResource\RelationManagers;
use App\Models\PharmacySupplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PharmacySupplierResource extends Resource
{
    protected static ?string $model = PharmacySupplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Pharmacy Suppliers';
    protected static ?string $modelLabel = 'Pharmacy Supplier';
    protected static ?string $pluralModelLabel = 'Pharmacy Suppliers';
    protected static ?string $navigationGroup = 'Pharmacy Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., ABC Pharmaceuticals'),
                        
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Contact Person')
                            ->maxLength(255)
                            ->placeholder('Primary contact name'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('(555) 123-4567'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('contact@supplier.com'),
                        
                        Forms\Components\TextInput::make('fax')
                            ->label('Fax')
                            ->maxLength(255)
                            ->placeholder('(555) 123-4568'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Street Address')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('state')
                            ->label('State')
                            ->options([
                                'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                'WI' => 'Wisconsin', 'WY' => 'Wyoming',
                            ])
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('zip')
                            ->label('ZIP Code')
                            ->maxLength(10)
                            ->placeholder('12345'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Business Details')
                    ->schema([
                        Forms\Components\TextInput::make('license_number')
                            ->label('License Number')
                            ->maxLength(255)
                            ->placeholder('Pharmacy license number'),
                        
                        Forms\Components\TextInput::make('default_discount')
                            ->label('Default Discount (%)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Default discount percentage for orders'),
                        
                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('Payment Terms (Days)')
                            ->numeric()
                            ->default(30)
                            ->minValue(0)
                            ->helperText('Number of days to pay invoices'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive suppliers will not appear in order forms'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional notes about this supplier...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact')
                    ->searchable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('city')
                    ->label('Location')
                    ->formatStateUsing(fn ($record) => $record->city . ($record->state ? ', ' . $record->state : ''))
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('default_discount')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . '%' : 'N/A')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
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
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            // OrdersRelationManager will be added later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPharmacySuppliers::route('/'),
            'create' => Pages\CreatePharmacySupplier::route('/create'),
            'view' => Pages\ViewPharmacySupplier::route('/{record}'),
            'edit' => Pages\EditPharmacySupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('orders');
    }
}
