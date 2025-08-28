<?php

namespace App\Support\Repositories\Document;

use App\Models\Document;
use App\Models\Documentable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

interface DocumentRepository
{
    public function store(DataCollection $files, Documentable $documentable): Collection;
    public function delete(Document $document): void;

}
