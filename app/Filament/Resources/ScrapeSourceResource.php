<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeSourceResource\Pages;
use App\Filament\Resources\ScrapeSourceResource\RelationManagers;
use App\Models\ScrapeSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScrapeSourceResource extends Resource
{
    protected static ?string $model = ScrapeSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Scrape Sources';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Label Name'),
                Forms\Components\Select::make('type')
                    ->options([
                        'bandcamp' => 'Bandcamp',
                        'shopify' => 'Shopify',
                        'instagram' => 'Instagram',
                        'custom' => 'Custom',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->url()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->default(true)
                    ->inline(false),
                Forms\Components\DateTimePicker::make('last_scraped_at')
                    ->label('Last Scraped')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->limit(50),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_scraped_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'bandcamp' => 'Bandcamp',
                        'shopify' => 'Shopify',
                        'instagram' => 'Instagram',
                        'custom' => 'Custom',
                    ]),
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
            ->defaultSort('name');
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
            'index' => Pages\ListScrapeSources::route('/'),
            'create' => Pages\CreateScrapeSource::route('/create'),
            'edit' => Pages\EditScrapeSource::route('/{record}/edit'),
        ];
    }
}
