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
// Dummy model for testing

function makeDataCollectionFromFiles(array $files): DataCollection {
    $items = array_map(fn(UploadedFile $f) => ImageData::from(['file' => $f]), $files);
    return new DataCollection(ImageData::class, collect($items));
}

// Probe Imagick format support once for conditional skips
$IMAGICK_SUPPORTS_HEIC = class_exists(\Imagick::class) && new Imagick()->queryFormats('HEIC') !== [];
$IMAGICK_SUPPORTS_AVIF = class_exists(\Imagick::class) && new Imagick()->queryFormats('AVIF') !== [];

it('sets disk and private correctly across file types', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();
    $jpg = UploadedFile::fake()->image('x.jpg', 100, 100);
    $files = makeDataCollectionFromFiles([$jpg]);
    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable, private: null);
    $doc = $out->first();
    expect($doc->disk)->toBe('public')->and($doc->private)->toBeNull();
});

it('processes each supported image extension', function () {
    $exts = ['webp','heic','tiff','avif'];
    foreach ($exts as $ext) {
        Storage::fake('public');
        $docable = TestDocumentable::create();
        $file = UploadedFile::fake()->image("sample.$ext", 60, 70);
        $files = makeDataCollectionFromFiles([$file]);
        $repo = app(DocumentRepositoryEloquent::class);
        $out = $repo->store($files, $docable);
        $doc = $out->first();
        expect($doc->width)->toBeGreaterThan(0)->and($doc->height)->toBeGreaterThan(0);
        Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
    }
});

it('throws an exception on corrupted image file', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();
    $bad = UploadedFile::fake()->create('bad.jpg', 1, 'text/plain');
    $files = makeDataCollectionFromFiles([$bad]);
    $repo = app(DocumentRepositoryEloquent::class);
    $this->expectException(Throwable::class);
    $repo->store($files, $docable);
});

it('stores to local (private) disk when private flag is provided and keeps basename as file_name', function () {
    Storage::fake('local');
    $docable = TestDocumentable::create();

    $png = UploadedFile::fake()->image('avatar.png', 200, 300);
    $files = makeDataCollectionFromFiles([$png]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable, collection: 'profile', private: '1');

    /** @var Document $doc */
    $doc = $out->first();

    expect($doc->collection)->toBe('profile')
        ->and($doc->disk)->toBe('local')
        ->and(pathinfo($doc->file_name, PATHINFO_EXTENSION))->not->toBe('');

    Storage::disk('local')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
});

it('returns empty collection when given an empty DataCollection', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    // DataCollection vide
    $empty = new DataCollection(ImageData::class, collect());

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($empty, $docable);

    expect($out)->toBeEmpty();
});

it('falls back to .jpg when HEIC not supported by Imagick', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $heic = UploadedFile::fake()->image('sample.heic', 120, 90);
    $files = makeDataCollectionFromFiles([$heic]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);
    $doc = $out->first();

    expect(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)))->toBe('jpg');
    Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
})->skip($IMAGICK_SUPPORTS_HEIC, 'Imagick supports HEIC here; fallback test not applicable.');

it('falls back to .jpg when AVIF not supported by Imagick', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $avif = UploadedFile::fake()->image('sample.avif', 120, 90);
    $files = makeDataCollectionFromFiles([$avif]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);
    $doc = $out->first();

    expect(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)))->toBe('jpg');
    Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
})->skip($IMAGICK_SUPPORTS_AVIF, 'Imagick supports AVIF here; fallback test not applicable.');



it('stores raw DNG as-is when Imagick cannot decode it', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    // faux DNG
    $dng = UploadedFile::fake()->create('fake.dng', 64, 'image/x-adobe-dng');
    $files = makeDataCollectionFromFiles([$dng]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);

    $doc = $out->first();
    // On a un .dng (fallback) et pas de dimensions
    expect(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)))->toBe('dng')
        ->and($doc->width)->toBeNull()
        ->and($doc->height)->toBeNull();

    Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
});

it('stores non-image text file and sets width/height to null', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $txt = UploadedFile::fake()->create('notes.txt', 2, 'text/plain');
    $files = makeDataCollectionFromFiles([$txt]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);
    $doc = $out->first();

    expect($doc->width)->toBeNull()
        ->and($doc->height)->toBeNull();

    Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
});

it('persists original client name on Document.name', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $file = UploadedFile::fake()->image('original_name.jpg', 80, 80);
    $files = makeDataCollectionFromFiles([$file]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable, collection: 'pics');
    $doc = $out->first();

    expect($doc->name)->toBe('original_name.jpg')
        ->and($doc->collection)->toBe('pics');
});

it('stores multiple files in one call', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $f1 = UploadedFile::fake()->image('img1.jpg', 50, 50);
    $f2 = UploadedFile::fake()->image('img2.png', 60, 60);
    $files = makeDataCollectionFromFiles([$f1, $f2]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);

    expect($out)->toHaveCount(2);
    foreach ($out as $doc) {
        Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
    }
});

it('deletes a document from database', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();
    $file = UploadedFile::fake()->image('to_delete.jpg', 100, 100);
    $files = makeDataCollectionFromFiles([$file]);

    $repo = app(DocumentRepositoryEloquent::class);
    $doc = $repo->store($files, $docable)->first();

    expect(Document::count())->toBe(1);

    $repo->delete($doc);

    expect(Document::count())->toBe(0);
});


it('stores a pdf file and sets dimensions null', function () {
    Storage::fake('public');
    $docable = TestDocumentable::create();

    $pdf = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
    $files = makeDataCollectionFromFiles([$pdf]);

    $repo = app(DocumentRepositoryEloquent::class);
    $out = $repo->store($files, $docable);
    $doc = $out->first();

    expect($doc->width)->toBeNull()
        ->and($doc->height)->toBeNull()
        ->and(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)))->toBe('pdf');

    Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
});

it('handles many file types in one run (images, raw, non-images) with graceful fallbacks', function () {
    $supports = function (string $fmt): bool {
        return class_exists(\Imagick::class)
            && (new \Imagick())->queryFormats(strtoupper($fmt)) !== [];
    };

    $cases = [
        // Basic raster images
        ['ext' => 'jpg',  'mime' => 'image/jpeg', 'kind' => 'image'],
        ['ext' => 'png',  'mime' => 'image/png',  'kind' => 'image'],
        // Formats that may require Imagick delegates → expect fallback to jpg if unsupported
        ['ext' => 'webp', 'mime' => 'image/webp', 'kind' => 'image', 'needs' => 'WEBP', 'fallback' => 'jpg'],
        ['ext' => 'heic', 'mime' => 'image/heic', 'kind' => 'image', 'needs' => 'HEIC', 'fallback' => 'jpg'],
        ['ext' => 'tiff', 'mime' => 'image/tiff', 'kind' => 'image', 'needs' => 'TIFF', 'fallback' => 'jpg'],
        ['ext' => 'avif', 'mime' => 'image/avif', 'kind' => 'image', 'needs' => 'AVIF', 'fallback' => 'jpg'],
        // Non-images
        ['ext' => 'pdf',  'mime' => 'application/pdf', 'kind' => 'file'],
        ['ext' => 'txt',  'mime' => 'text/plain',      'kind' => 'file'],
        // RAW DNG → may decode to jpg if Imagick can, otherwise stored as-is (.dng)
        ['ext' => 'dng',  'mime' => 'image/x-adobe-dng', 'kind' => 'raw'],
    ];

    foreach ($cases as $c) {
        Storage::fake('public');
        $docable = TestDocumentable::create();

        // Build the UploadedFile depending on type
        if ($c['kind'] === 'image') {
            $file = UploadedFile::fake()->image('sample.' . $c['ext'], 64, 64);
        } elseif ($c['kind'] === 'file') {
            $file = UploadedFile::fake()->create('sample.' . $c['ext'], 10, $c['mime']);
        } else { // raw (dng)
            $file = UploadedFile::fake()->create('sample.dng', 64, 'image/x-adobe-dng');
        }

        $files = makeDataCollectionFromFiles([$file]);
        $repo  = app(DocumentRepositoryEloquent::class);
        $out   = $repo->store($files, $docable);
        /** @var Document $doc */
        $doc   = $out->first();

        $finalExt = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));

        // Determine expected extension for formats that need Imagick support
        if (($c['kind'] === 'image') && isset($c['needs'])) {
            $supported = $supports($c['needs']);
            $expected  = $supported ? $c['ext'] : ($c['fallback'] ?? 'jpg');
            expect($finalExt)->toBe($expected);
        }

        // Non-images must keep their extension and have no dimensions
        if ($c['kind'] === 'file') {
            expect($finalExt)->toBe($c['ext'])
                ->and($doc->width)->toBeNull()
                ->and($doc->height)->toBeNull();
        }

        // Basic images: should have dimensions > 0 (except RAW special case below)
        if ($c['kind'] === 'image') {
            expect($doc->width)->toBeGreaterThan(0)
                ->and($doc->height)->toBeGreaterThan(0);
        }

        // RAW DNG: depending on environment, either decoded to JPG (dims set) or stored as-is (.dng)
        if ($c['kind'] === 'raw') {
            if ($finalExt === 'jpg') {
                expect($doc->width)->toBeGreaterThan(0)
                    ->and($doc->height)->toBeGreaterThan(0);
            } else {
                expect($finalExt)->toBe('dng')
                    ->and($doc->width)->toBeNull()
                    ->and($doc->height)->toBeNull();
            }
        }

        Storage::disk('public')->assertExists("test_docs/{$docable->id}/{$doc->file_name}");
    }
});
