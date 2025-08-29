<?php

use App\Models\Document;
use App\Models\User;
use App\Support\Repositories\User\UserRepositoryEloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Support\Repositories\Document\DocumentRepositoryEloquent;
use Spatie\LaravelData\DataCollection;
use App\Support\Data\ImageData;
use App\Models\Test\TestDocumentable;

uses(TestCase::class, RefreshDatabase::class);



// -------------------------------------------------
// changePassword()
// -------------------------------------------------

test('changePassword updates hash and persists to db', function () {
    $user = User::query()->create([
        'email' => 'pass@example.test',
        'password' => Hash::make('old-secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $updated = userRepo()->changePassword($user, 'new-secret');

    expect(Hash::check('new-secret', $updated->password))->toBeTrue()
        ->and(Hash::check('old-secret', $updated->password))->toBeFalse()
        ->and(User::find($user->id)->password)->toBe($updated->password);
});

test('changePassword modifies updated_at timestamp when changed', function () {
    $user = User::query()->create([
        'email' => 'time@example.test',
        'password' => Hash::make('abc'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $user->updated_at = now()->subDay();
    $user->saveQuietly();
    $before = $user->updated_at;

    $updated = userRepo()->changePassword($user, 'xyz');

    expect($updated->updated_at->greaterThan($before))->toBeTrue();
});

test('changePassword keeps other attributes unchanged', function () {
    $user = User::query()->create([
        'email' => 'keep@example.test',
        'password' => Hash::make('old'),
        'accept_terms' => true,
        'plan' => 'premium',
    ]);

    $updated = userRepo()->changePassword($user, 'brandnew');

    expect($updated->plan)->toBe('premium')
        ->and($updated->accept_terms)->toBeTrue()
        ->and($updated->email)->toBe('keep@example.test');
});

test('changePassword returns same user id', function () {
    $user = User::query()->create([
        'email' => 'id@example.test',
        'password' => Hash::make('init'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $result = userRepo()->changePassword($user, 'another');

    expect($result->id)->toBe($user->id);
});

test('changePassword can be called multiple times in sequence', function () {
    $user = User::query()->create([
        'email' => 'multi@example.test',
        'password' => Hash::make('first'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    userRepo()->changePassword($user, 'second');
    $final = userRepo()->changePassword($user->fresh(), 'third');

    expect(Hash::check('third', $final->password))->toBeTrue()
        ->and(Hash::check('second', $final->password))->toBeFalse();
});

test('changePassword hashes even if input looks like a hash', function () {
    $fakeHash = Hash::make('real-secret');

    $user = User::query()->create([
        'email' => 'hashy@example.test',
        'password' => Hash::make('init'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $updated = userRepo()->changePassword($user, $fakeHash);

    // It should not store the raw input string, but re-hash it
    expect($updated->password)->not->toBe($fakeHash);
});
