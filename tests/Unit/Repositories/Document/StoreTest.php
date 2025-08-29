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
// store()
// -----------------------

test('store saves a JPG image on public disk with resized dimensions', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();
    $files = makeDataCollectionFromFiles([
        UploadedFile::fake()->image('photo.jpg', 2000, 1500),
    ]);

    $docs = documentRepo()->store($files, $documentable, collection: 'avatar');

    expect($docs)->toHaveCount(1);
    /** @var Document $doc */
    $doc = $docs->first();

    expect($doc->disk)->toBe('public')
        ->and($doc->collection)->toBe('avatar')
        ->and($doc->private)->toBeNull()
        ->and($doc->width)->toBeLessThanOrEqual(1000)
        ->and($doc->height)->toBeLessThanOrEqual(1000)
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->toBe('jpg');

    Storage::disk('public')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});


test('store keeps PNG extension when supported and records dimensions', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();
    $files = makeDataCollectionFromFiles([
        UploadedFile::fake()->image('image.png', 1800, 1200),
    ]);

    $docs = documentRepo()->store($files, $documentable);

    $doc = $docs->first();
    expect($doc->disk)->toBe('public')
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->toBe('png')
        ->and($doc->width)->toBeLessThanOrEqual(1000)
        ->and($doc->height)->toBeLessThanOrEqual(1000);

    Storage::disk('public')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});


test('store falls back to JPG when extension unsupported (e.g., AVIF)', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();
    // fake image content with .avif extension
    $files = makeDataCollectionFromFiles([
        UploadedFile::fake()->image('pic.avif', 1600, 900),
    ]);

    $docs = documentRepo()->store($files, $documentable);

    $doc = $docs->first();
    expect($doc->disk)->toBe('public')
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->toBe('jpg');

    Storage::disk('public')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});


test('store uses local disk when private flag is provided', function () {
    Storage::fake('local');

    $documentable = TestDocumentable::query()->create();
    $files = makeDataCollectionFromFiles([
        UploadedFile::fake()->image('secret.jpg', 1200, 1200),
    ]);

    $docs = documentRepo()->store($files, $documentable, collection: 'secure', private: '1');

    $doc = $docs->first();
    expect($doc->disk)->toBe('local')
        ->and($doc->collection)->toBe('secure')
        ->and($doc->private)->toBe('1');

    Storage::disk('local')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});


test('store handles non-image files (pdf) without dimensions', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();
    $pdf = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
    $files = makeDataCollectionFromFiles([$pdf]);

    $docs = documentRepo()->store($files, $documentable, collection: 'docs');

    $doc = $docs->first();
    expect($doc->disk)->toBe('public')
        ->and($doc->width)->toBeNull()
        ->and($doc->height)->toBeNull()
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->toBe('pdf');

    Storage::disk('public')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});


test('store falls back for DNG: stores raw dng and no dimensions when Imagick fails', function () {
    Storage::fake('public');

    $documentable = TestDocumentable::query()->create();
    // create a fake .dng (not a real DNG; Imagick should fail -> fallback path)
    $dng = UploadedFile::fake()->create('raw.dng', 20, 'image/x-adobe-dng');
    $files = makeDataCollectionFromFiles([$dng]);

    $docs = documentRepo()->store($files, $documentable);

    $doc = $docs->first();
    expect($doc->disk)->toBe('public')
        ->and($doc->width)->toBeNull()
        ->and($doc->height)->toBeNull()
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->toBe('dng');

    Storage::disk('public')->assertExists($documentable->storagePath() . '/' . $doc->file_name);
});
