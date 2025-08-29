<?php

use App\Features\User\Data\UpdateMailData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 29, 12, 0, 0));
});

// Helper expected to be provided globally by the project per user's instruction
// function userRepo(): UserRepositoryEloquent { return app(UserRepositoryEloquent::class); }

// -------------------------------------------------
// changeEmail()
// -------------------------------------------------

test('changeEmail updates email and persists to database', function () {
    /** @var User $user */
    $user = User::query()->create([
        'email' => 'old@example.test',
        'password' => Hash::make('secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $data = makeUpdateEmailData(email: 'new@example.test');

    $updated = userRepo()->changeEmail($user, $data);

    expect($updated->email)->toBe('new@example.test')
        ->and(User::find($user->id)->email)->toBe('new@example.test');
});


test('changeEmail preserves other attributes and bumps updated_at when value changed', function () {
    $user = User::query()->create([
        'email' => 'first@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);
    $user->updated_at = now()->subDay();
    $user->saveQuietly();
    $beforeUpdatedAt = $user->updated_at->copy();

    $updated = userRepo()->changeEmail($user, makeUpdateEmailData(email: 'second@example.test'));

    expect($updated->plan)->toBe('free')
        ->and($updated->accept_terms)->toBeTrue()
        ->and($updated->updated_at->greaterThan($beforeUpdatedAt))->toBeTrue();
});


test('changeEmail is a no-op for timestamps when setting the same email', function () {
    $user = User::query()->create([
        'email' => 'same@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    // Ensure distinct updated_at base
    $user->updated_at = now()->subMinute();
    $user->save();

    $before = $user->fresh()->updated_at->copy();

    $updated = userRepo()->changeEmail($user->fresh(), makeUpdateEmailData(email: 'same@example.test'));

    // Eloquent does not touch timestamps when the attribute is not dirty
    expect($updated->updated_at->eq($before))->toBeTrue()
        ->and(User::find($user->id)->updated_at->eq($before))->toBeTrue();
});


test('changeEmail stores exact casing and plus-tags (no normalization)', function () {
    $user = User::query()->create([
        'email' => 'base@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $mail = 'MiXeD+tag@Example.TEST';
    $updated = userRepo()->changeEmail($user, makeUpdateEmailData(email: $mail));

    expect($updated->email)->toBe($mail)
        ->and(User::find($user->id)->email)->toBe($mail);
});


test('changeEmail can be called multiple times in sequence', function () {
    $user = User::query()->create([
        'email' => 'first@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    userRepo()->changeEmail($user, makeUpdateEmailData(email: 'second@example.test'));
    $final = userRepo()->changeEmail($user->fresh(), makeUpdateEmailData(email: 'third@example.test'));

    expect($final->email)->toBe('third@example.test')
        ->and(User::find($user->id)->email)->toBe('third@example.test');
});


test('changeEmail returns the same user instance (id unchanged)', function () {
    $user = User::query()->create([
        'email' => 'oldie@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $ret = userRepo()->changeEmail($user, makeUpdateEmailData(email: 'brandnew@example.test'));

    expect($ret->id)->toBe($user->id);
});

