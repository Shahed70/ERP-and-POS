<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Product Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Product Categories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Category Information'))
                    ->description(__('Define fish feed and medicine categories.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Category Name (নাম)'))
                            ->placeholder(__('e.g. Floating Feed, Sinking Feed, Nursery Feed'))
                            ->required()
                            ->maxLength(100)
                            ->unique(ProductCategory::class, 'name', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $operation) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('Slug (স্লাগ)'))
                            ->placeholder(__('auto-generated-slug'))
                            ->required()
                            ->maxLength(120)
                            ->unique(ProductCategory::class, 'slug', ignoreRecord: true)
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Description (বিবরণ)'))
                            ->placeholder(__('Brief notes regarding feed type, pellet size range, or suitable fish species.'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active Status (সক্রিয়)'))
                            ->helperText(__('Inactive categories will be hidden from daily POS sale entry.'))
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Category Name (নাম)'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ProductCategory $record): ?string => $record->description ? Str::limit($record->description, 50) : null),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status (অবস্থা)'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At (তৈরির তারিখ)'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Filter by Status (অবস্থা)'))
                    ->boolean()
                    ->trueLabel(__('Active Only'))
                    ->falseLabel(__('Inactive Only')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, ProductCategory $record) {
                        if ($record->products()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title(__('Cannot Delete Category'))
                                ->body(__('This category contains linked products and cannot be deleted.'))
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
