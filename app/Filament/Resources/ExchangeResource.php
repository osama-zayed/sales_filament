<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeResource\Pages;
use App\Filament\Resources\ExchangeResource\RelationManagers;
use App\Models\Exchange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExchangeResource extends Resource
{
    protected static ?string $model = Exchange::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'حركة مخزنية';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'صرف';
    protected static ?string $pluralLabel = 'المبيعات';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('exchange_date')
                    ->label('تاريخ المبيعات')
                    ->default(today())
                    ->required(),
                Forms\Components\TextInput::make('exchange_name')
                    ->label('اسم العميل')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->label('اجمالي الفاتوره')
                    ->numeric(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->label('الملاحظات')
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('exchange_details')
                    ->relationship('exchangeDetails')
                    ->schema([
                        Forms\Components\TextInput::make('exchange_id')
                            ->required()
                            ->hidden()
                            ->numeric(),
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', titleAttribute: 'product_name')
                            ->label('المنتج')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('unit_id')
                            ->relationship('unit', titleAttribute: 'unit_name')
                            ->label('الوحده')
                            ->searchable()
                            ->preload()
                            ->required(),
                            Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->label('الكمية')
                            ->numeric(),
                        Forms\Components\TextInput::make('unit_price')
                            ->required()
                            ->label('سعر الوحده')
                            ->numeric(),
                            Forms\Components\TextInput::make('total_price')
                            ->required()
                            ->label('اجمالي السعر')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $quantity = $set('quantity');
                                $unit_price = $set('unit_price');
                                $set('total_price', $quantity * $unit_price);
                            }),
                    ])->columns(4)
                    ->columnSpan(2)
                    ->label('تفاصيل المبيعات'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                ->date()
                ->label('رقم الفاتوره')
                ->sortable(),
                Tables\Columns\TextColumn::make('exchange_date')
                    ->label('تاريخ المبيعات')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exchange_name')
                    ->label('اسم العميل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('اجمالي الفاتوره')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('وقت الاضافة')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('وقت التعديل')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
            'index' => Pages\ListExchanges::route('/'),
            'create' => Pages\CreateExchange::route('/create'),
            'view' => Pages\ViewExchange::route('/{record}'),
            'edit' => Pages\EditExchange::route('/{record}/edit'),
        ];
    }
}
