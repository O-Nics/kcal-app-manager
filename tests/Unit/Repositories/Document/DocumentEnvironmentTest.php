<?php

use App\Models\Document;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Support\Repositories\Document\DocumentRepositoryEloquent;
use Spatie\LaravelData\DataCollection;
use App\Support\Data\ImageData;
use App\Models\Test\TestDocumentable;

uses(TestCase::class, RefreshDatabase::class);


function makeDataCollectionFromFiles(array $files): DataCollection {
    $items = array_map(fn(UploadedFile $f) => ImageData::from(['file' => $f]), $files);
    return new DataCollection(ImageData::class, collect($items));
}

function documentRepo(): DocumentRepositoryEloquent {
    return app(DocumentRepositoryEloquent::class);
}
