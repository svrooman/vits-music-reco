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
                Forms\Components\TagsInput::make('tags')
                    ->placeholder('Add tags (e.g., electronic, ambient, 2025)')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('discovered_at')
                    ->default(now()),
                Forms\Components\Toggle::make('tidal_added')
                    ->label('Added to Tidal')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('tidal_added_at')
                    ->label('Tidal Added At')
                    ->disabled(),
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
                Tables\Columns\IconColumn::make('tidal_added')
                    ->label('Tidal')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags')
                    ->badge()
                    ->separator(','),
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
                Tables\Filters\TernaryFilter::make('tidal_added')
                    ->label('Added to Tidal'),
            ])
            ->actions([
                Tables\Actions\Action::make('addToTidal')
                    ->label('Add to Tidal')
                    ->icon('heroicon-o-musical-note')
                    ->color('success')
                    ->hidden(fn ($record) => $record->tidal_added)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // TODO: Implement Tidal OAuth and add logic
                        $record->update([
                            'tidal_added' => true,
                            'tidal_added_at' => now(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('addToTidal')
                        ->label('Add to Tidal')
                        ->icon('heroicon-o-musical-note')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->tidal_added) {
                                    // TODO: Implement Tidal OAuth and add logic
                                    $record->update([
                                        'tidal_added' => true,
                                        'tidal_added_at' => now(),
                                    ]);
                                }
                            }
                        }),
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
