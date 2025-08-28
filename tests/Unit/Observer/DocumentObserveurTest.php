<?php

use App\Features\Auth\Services\TokenPair;
use App\Features\Auth\Services\TokenService;
use App\Models\Document;
use App\Models\Test\TestDocumentable;
use App\Models\User;
use App\Support\Enums\TokenAbility;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Laravel\Sanctum\PersonalAccessToken;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Valeurs par défaut (tu peux les changer par config si besoin)
    Schema::create('test_documentables', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

});


// --- Fixtures: simple Documentable for tests ---


function makeDoc(array $overrides = []): Document {
    $docable = TestDocumentable::create();
    $doc = new Document(array_merge([
        'name'      => 'sample',
        'file_name' => 'foo.jpg',
        'disk'      => 'public',
        'collection'=> 'tests',
        'private'   => null,
        'width'     => null,
        'height'    => null,
    ], $overrides));
    $doc->documentable()->associate($docable);
    $doc->save(); // triggers creating observer
    return $doc->fresh();
}

// 1) Token set on creating and is 64 chars
it('sets a 64-char random token on creating', function () {
    $doc = makeDoc();
    expect($doc->token)->toBeString()->toHaveLength(64);
});

// 2) Token differs across different documents
it('generates a different token for different documents', function () {
    $a = makeDoc();
    $b = makeDoc();
    expect($a->token)->not->toBe($b->token);
});

// 3) Token is not changed on update (observer only runs on creating)
it('does not change token on update', function () {
    $doc = makeDoc();
    $token = $doc->token;
    $doc->name = 'updated';
    $doc->save();
    expect($doc->refresh()->token)->toBe($token);
});

// 4) Overridden preset token is replaced on creating
it('overrides any preset token on creating', function () {
    $docable = TestDocumentable::create();
    $doc = new Document([
        'name' => 'preset', 'file_name' => 'x.jpg', 'disk' => 'public', 'token' => 'abc',
    ]);
    $doc->documentable()->associate($docable);
    $doc->save();
    expect($doc->token)->toHaveLength(64)->not->toBe('abc');
});

// 5) Deletes file on public disk on deleted()
it('deletes the file from public disk on deleted', function () {
    Storage::fake('public');
    $doc = makeDoc(['disk' => 'public', 'file_name' => 'bar.png']);
    $path = $doc->documentable->storagePath()."/".$doc->file_name;
    Storage::disk('public')->put($path, 'content');
    Storage::disk('public')->assertExists($path);

    $doc->delete(); // triggers deleted observer

    Storage::disk('public')->assertMissing($path);
});

// 6) Deletes file on local disk on deleted()
it('deletes the file from local disk on deleted', function () {
    Storage::fake('local');
    $doc = makeDoc(['disk' => 'local', 'file_name' => 'baz.webp']);
    $path = $doc->documentable->storagePath()."/".$doc->file_name;
    Storage::disk('local')->put($path, 'content');
    Storage::disk('local')->assertExists($path);

    $doc->delete();

    Storage::disk('local')->assertMissing($path);
});

// 7) Logs info with document id on deleted()
it('logs an info message with document id on deleted', function () {
    Log::spy();
    Storage::fake('public');
    $doc = makeDoc(['disk' => 'public']);

    $doc->delete();

    Log::shouldHaveReceived('info')->once()->withArgs(function ($msg) use ($doc) {
        return is_string($msg) && str_contains($msg, "Document deleted: ".$doc->id);
    });
});

// 8) Uses documentable storagePath correctly (no double slashes)
it('uses documentable storagePath to build deletion path', function () {
    Storage::fake('public');
    $doc = makeDoc(['disk' => 'public', 'file_name' => 'inner.jpg']);
    $expected = $doc->documentable->storagePath()."/".$doc->file_name;
    Storage::disk('public')->put($expected, 'x');

    $doc->delete();

    Storage::disk('public')->assertMissing($expected);
});

// 9) No exception when file is missing (delete is idempotent)
it('does not throw if file does not exist on disk', function () {
    Storage::fake('public');
    $doc = makeDoc(['disk' => 'public', 'file_name' => 'absent.png']);

    // Do not create the file; delete should not throw
    $doc->delete();

    expect(true)->toBeTrue(); // reached
});

// 10) Works with file names containing spaces
it('handles file names with spaces', function () {
    Storage::fake('public');
    $doc = makeDoc(['disk' => 'public', 'file_name' => 'my file 01.jpg']);
    $path = $doc->documentable->storagePath()."/".$doc->file_name;
    Storage::disk('public')->put($path, 'y');

    $doc->delete();

    Storage::disk('public')->assertMissing($path);
});

// 11) Deleting from one disk does not touch files on another
it('does not delete files on a different disk', function () {
    Storage::fake('public');
    Storage::fake('local');

    $doc = makeDoc(['disk' => 'public', 'file_name' => 'keepme.pdf']);
    $publicPath = $doc->documentable->storagePath()."/".$doc->file_name;
    $localPath  = $doc->documentable->storagePath()."/".$doc->file_name;

    Storage::disk('public')->put($publicPath, 'pub');
    Storage::disk('local')->put($localPath, 'loc');

    $doc->delete();

    Storage::disk('public')->assertMissing($publicPath);
    Storage::disk('local')->assertExists($localPath); // untouched
});

// 12) Trailing slash in storagePath still deletes correctly
it('deletes correctly even if storagePath has trailing slash', function () {
    Storage::fake('public');

    // Custom docable with trailing slash
    $docable = new class extends TestDocumentable {
        public function storagePath(): string { return parent::storagePath().'/'; }
    };
    $docable->save();

    $doc = new Document(['name' => 't', 'file_name' => 't.jpg', 'disk' => 'public']);
    $doc->documentable()->associate($docable);
    $doc->save();

    $path = rtrim($docable->storagePath(), '/')."/".$doc->file_name;
    Storage::disk('public')->put($path, 'z');

    $doc->delete();

    Storage::disk('public')->assertMissing($path);
});

// 13) Multiple documents under same folder: deleting one keeps the other
it('deleting one document keeps other files intact in the same folder', function () {
    Storage::fake('public');
    $docA = makeDoc(['disk' => 'public', 'file_name' => 'a.jpg']);
    $docB = makeDoc(['disk' => 'public', 'file_name' => 'b.jpg', 'documentable_id' => $docA->documentable_id, 'documentable_type' => $docA->documentable_type]);

    $base = $docA->documentable->storagePath();
    Storage::disk('public')->put("$base/a.jpg", 'A');
    Storage::disk('public')->put("$base/b.jpg", 'B');

    $docA->delete();

    Storage::disk('public')->assertMissing("$base/a.jpg");
    Storage::disk('public')->assertExists("$base/b.jpg");
});
