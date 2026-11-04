<?php

namespace App\Filament\Resources\Transcriptions\Pages;

use App\Filament\Actions\RembourserTranscription;
use App\Filament\Resources\Transcriptions\TranscriptionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTranscription extends ViewRecord
{
    protected static string $resource = TranscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RembourserTranscription::make(),
        ];
    }
}
