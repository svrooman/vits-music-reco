<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscoveredAlbumResource\Pages;
use App\Filament\Resources\DiscoveredAlbumResource\RelationManagers;
use App\Models\DiscoveredAlbum;
use App\Services\TidalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                    ->form(function ($record) {
                        $user = Auth::user();

                        if (!$user->tidal_access_token) {
                            return [];
                        }

                        $tidalService = app(TidalService::class);
                        $matches = $tidalService->searchAlbums($record->artist, $record->album, $user->tidal_access_token);

                        if (empty($matches)) {
                            return [];
                        }

                        $options = [];
                        foreach ($matches as $match) {
                            $title = $match['attributes']['title'] ?? 'Unknown';
                            $releaseDate = isset($match['attributes']['releaseDate'])
                                ? date('Y', strtotime($match['attributes']['releaseDate']))
                                : '';
                            $label = $releaseDate ? "{$title} ({$releaseDate})" : $title;
                            $options[$match['id']] = $label;
                        }

                        return [
                            Forms\Components\Radio::make('album_id')
                                ->label('Select Album')
                                ->options($options)
                                ->required()
                                ->default(array_key_first($options)),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $user = Auth::user();

                        if (!$user->tidal_access_token) {
                            Notification::make()
                                ->title('Connect to Tidal first')
                                ->warning()
                                ->body('Please connect your Tidal account.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('connect')
                                        ->button()
                                        ->url(route('tidal.auth')),
                                ])
                                ->send();
                            return;
                        }

                        if (empty($data['album_id'])) {
                            Notification::make()
                                ->title('No album selected')
                                ->danger()
                                ->send();
                            return;
                        }

                        $tidalService = app(TidalService::class);
                        $result = $tidalService->addAlbumById($data['album_id'], $user->tidal_access_token);

                        if ($result['success']) {
                            $record->update([
                                'tidal_added' => true,
                                'tidal_added_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Added to Tidal')
                                ->success()
                                ->body($result['message'])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Failed to add to Tidal')
                                ->danger()
                                ->body($result['message'])
                                ->send();
                        }
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
                            $user = Auth::user();

                            if (!$user->tidal_access_token) {
                                Notification::make()
                                    ->title('Connect to Tidal first')
                                    ->warning()
                                    ->body('Please connect your Tidal account.')
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('connect')
                                            ->button()
                                            ->url(route('tidal.auth')),
                                    ])
                                    ->send();
                                return;
                            }

                            $tidalService = app(TidalService::class);
                            $added = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (!$record->tidal_added) {
                                    $result = $tidalService->searchAndAddAlbum(
                                        $record->artist,
                                        $record->album,
                                        $user->tidal_access_token
                                    );

                                    if ($result['success']) {
                                        $record->update([
                                            'tidal_added' => true,
                                            'tidal_added_at' => now(),
                                        ]);
                                        $added++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }

                            Notification::make()
                                ->title('Bulk add complete')
                                ->success()
                                ->body("Added {$added} albums to Tidal. {$failed} failed.")
                                ->send();
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
