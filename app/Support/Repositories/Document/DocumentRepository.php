<?php

namespace App\Support\Repositories\Document;

use App\Models\Document;
use App\Models\Documentable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

interface DocumentRepository
{
    public function store(array $files, Documentable $documentable,?string $collection = null, ?string $private = null): Collection;
    public function delete(Document $document): void;
    public function downloadImage(string $url): ?UploadedFile;


}
