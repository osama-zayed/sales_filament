<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeResource\Pages;
use App\Filament\Resources\ExchangeResource\RelationManagers\ExchangeDetailsRelationManager;
use App\Models\Exchange;
use App\Models\ExchangeDetails;
use App\Models\Inventory;
use App\Models\ProductUnit;
use App\Models\Supply;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Session;
use PhpParser\Node\Stmt\Global_;

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
                TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->numeric()
                    ->required(),
                Forms\Components\DatePicker::make('exchange_date')
                    ->label('تاريخ المبيعات')
                    ->default(today())
                    ->required(),
                Select::make('inventory_id')
                    ->relationship('Inventory', 'name')
                    ->label('المخزن')
                    ->columnSpanFull()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        Session::put('inventory_id', $state);
                    }),

                TextInput::make('exchange_name')
                    ->label('اسم العميل')
                    ->required()
                    ->maxLength(255),
                TextInput::make('total_amount')
                    ->required()
                    ->label('اجمالي الفاتوره')
                    ->numeric()
                    ->default(function (callable $get) {
                        return Session::get('total_amount');
                    })
                    ->live()
                    ->readOnly()
                    ->reactive(),
                Textarea::make('notes')
                    ->maxLength(65535)
                    ->label('الملاحظات')
                    ->columnSpanFull(),
                Repeater::make('exchangeDetails')
                    ->relationship('exchangeDetails')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'product_name')
                            ->label('المنتج')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updatePriceAndTotal($get, $set);
                                self::updateRemainingQuantity($get, $set);
                            })
                            ->required(),
                        Select::make('unit_id')
                            ->relationship('unit', 'unit_name')
                            ->label('وحده القياس')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updatePriceAndTotal($get, $set);
                                self::updateRemainingQuantity($get, $set);
                            })
                            ->required(),
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->required()
                            ->live()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(function (callable $set, callable $get) {
                                $maxQuantity = $get('max_quantity') ?? 0;
                                return $maxQuantity;
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updatePriceAndTotal($get, $set);
                                self::updateRemainingQuantity($get, $set);
                            })
                            ->reactive(),
                        TextInput::make('unit_price')
                            ->label('سعر الوحدة')
                            ->required()
                            ->numeric()
                            ->live()
                            ->reactive()
                            ->default(function (callable $get) {
                                return $get('unit_price');
                            })
                            ->readonly(),
                        TextInput::make('total_price')
                            ->label('السعر الاجمالي')
                            ->required()
                            ->numeric()
                            ->live()
                            ->reactive()
                            ->default(function (callable $get) {
                                return $get('total_price');
                            })
                            ->readonly(),
                    ])
                    ->label('تفاصيل الصرف')
                    ->columns(3)
                    ->columnSpanFull()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updatePriceAndTotal($get, $set);
                        self::updateRemainingQuantity($get, $set);
                    })
                    // ->deleteAction(
                    //     fn (Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
                    // )
                    ->reorderable(false)
            ])
            ->columns(2);
    }

    public static function updatePriceAndTotal(Get $get, Set $set)
    {
        $selectedProducts = collect($get('product_id'));
        $selectedUnits = collect($get('unit_id'));
        $quantity = collect($get('quantity'));

        if (!$selectedProducts->isEmpty() && !$selectedUnits->isEmpty() && !$quantity->isEmpty()) {
            $prices = ProductUnit::where('product_id', $selectedProducts)
                ->where('unit_id', $selectedUnits)
                ->pluck('product_price', 'id')->first();
            $total_price = $prices * $quantity->first();
            $set('unit_price', $prices);
            $set('total_price', $total_price);
            Session::put('total_amount',  $total_price);
        }
    }

    public static function updateRemainingQuantity(Get $get, Set $set)
    {
        $selectedProducts = collect($get('product_id'));
        $selectedUnits = collect($get('unit_id'));
        $quantity = collect($get('quantity'));
        $inventoryId = Session::get('inventory_id');

        if (!$selectedProducts->isEmpty() && !$selectedUnits->isEmpty() && $inventoryId && !$quantity->isEmpty()) {
            $inventory = Supply::where('inventory_id', $inventoryId)
                ->whereHas('SupplyDetails', function ($query) use ($selectedProducts, $selectedUnits) {
                    $query->whereIn('product_id', $selectedProducts)
                        ->whereIn('unit_id', $selectedUnits);
                })
                ->with('SupplyDetails')
                ->get();

            $totalSuppliedQuantity = $inventory->sum(function ($supply) {
                return $supply->SupplyDetails->sum('quantity');
            });

            $totalUsedQuantity = ExchangeDetails::whereIn('product_id', $selectedProducts)
                ->whereIn('unit_id', $selectedUnits)
                ->sum('quantity');

            $remainingQuantity = $totalSuppliedQuantity - $totalUsedQuantity;
            $set('max_quantity', $remainingQuantity);

            if ($quantity->first() > $remainingQuantity) {
                Notification::make()
                    ->danger()
                    ->title('خطاء')
                    ->body('الكمية المتبقية في المخزن من هذا المنتج هي ' . $remainingQuantity)
                    ->send();
            }
        }
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->numeric()
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
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExchangeDetailsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchanges::route('/'),
            'create' => Pages\CreateExchange::route('/create'),
            'view' => Pages\ViewExchange::route('/{record}'),
            // 'edit' => Pages\EditExchange::route('/{record}/edit'),
        ];
    }
}
