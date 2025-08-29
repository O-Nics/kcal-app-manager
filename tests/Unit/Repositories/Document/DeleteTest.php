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

beforeEach(function () {
    Schema::create('test_documentables', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});


// -----------------------
// delete()
// -----------------------

test('delete removes the document', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();

    $doc = new Document([
        'name' => 'file.pdf',
        'file_name' => 'file.pdf',
        'disk' => 'public',
    ]);
    $doc->documentable()->associate($documentable);
    $doc->save();

    documentRepo()->delete($doc);

    expect(Document::find($doc->id))->toBeNull();
});


test('delete is idempotent when called twice', function () {
    $documentable = TestDocumentable::query()->create();

    $doc = new Document([
        'name' => 'doc',
        'file_name' => 'doc.pdf',
        'disk' => 'public',
    ]);
    $doc->documentable()->associate($documentable);
    $doc->save();

    documentRepo()->delete($doc);
    documentRepo()->delete($doc);

    expect(Document::find($doc->id))->toBeNull();
});


test('delete only affects the given document', function () {
    $documentable = TestDocumentable::query()->create();

    $doc1 = new Document([
        'name' => 'a',
        'file_name' => 'a.pdf',
        'disk' => 'public',
    ]);
    $doc1->documentable()->associate($documentable);
    $doc1->save();

    $doc2 = new Document([
        'name' => 'b',
        'file_name' => 'b.pdf',
        'disk' => 'public',
    ]);
    $doc2->documentable()->associate($documentable);
    $doc2->save();

    documentRepo()->delete($doc1);

    expect(Document::find($doc1->id))->toBeNull()
        ->and(Document::find($doc2->id))->not->toBeNull();
});


test('delete works with freshly retrieved model instance', function () {
    $documentable = TestDocumentable::query()->create();

    $doc = new Document([
        'name' => 'fresh',
        'file_name' => 'fresh.pdf',
        'disk' => 'public',
    ]);
    $doc->documentable()->associate($documentable);
    $doc->save();

    $fresh = Document::find($doc->id);
    documentRepo()->delete($fresh);

    expect(Document::find($doc->id))->toBeNull();
});
