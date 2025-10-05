<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscoveredAlbumResource\Pages;
use App\Filament\Resources\DiscoveredAlbumResource\RelationManagers;
use App\Models\DiscoveredAlbum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscoveredAlbumResource extends Resource
{
    protected static ?string $model = DiscoveredAlbum::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'Discovered Albums';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->options([
                        'dandelion' => 'Dandelion Records',
                        'bandcamp' => 'Bandcamp',
                        'instagram' => 'Instagram',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('artist')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('album')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('image_url')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('discovered_at')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Cover'),
                Tables\Columns\TextColumn::make('artist')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('album')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discovered_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'dandelion' => 'Dandelion Records',
                        'bandcamp' => 'Bandcamp',
                        'instagram' => 'Instagram',
                        'other' => 'Other',
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
            ->defaultSort('discovered_at', 'desc');
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
            'index' => Pages\ListDiscoveredAlbums::route('/'),
            'create' => Pages\CreateDiscoveredAlbum::route('/create'),
            'edit' => Pages\EditDiscoveredAlbum::route('/{record}/edit'),
        ];
    }
}
