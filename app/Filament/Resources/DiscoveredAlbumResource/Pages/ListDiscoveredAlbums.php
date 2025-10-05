<?php

namespace App\Filament\Resources\DiscoveredAlbumResource\Pages;

use App\Filament\Resources\DiscoveredAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDiscoveredAlbums extends ListRecords
{
    protected static string $resource = DiscoveredAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
