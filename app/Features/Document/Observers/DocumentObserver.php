<?php

namespace App\Features\Document\Observers;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentObserver
{

    public function creating(Document $document):void {
        $document->token = Str::random(64);
    }

    public function deleted(Document $document): void {
        Log::info("Document deleted: ".$document->id);
        $path = $document->documentable->storagePath()."/".$document->file_name;
        Storage::disk($document->disk)->delete($path);
    }

}
