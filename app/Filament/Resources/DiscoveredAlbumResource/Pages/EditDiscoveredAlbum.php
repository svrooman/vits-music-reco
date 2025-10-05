<?php

namespace App\Filament\Resources\DiscoveredAlbumResource\Pages;

use App\Filament\Resources\DiscoveredAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiscoveredAlbum extends EditRecord
{
    protected static string $resource = DiscoveredAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
