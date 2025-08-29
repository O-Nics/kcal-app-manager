<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// -------------------------------------------------
// checkPassword()
// -------------------------------------------------

test('checkPassword returns true for correct plain password', function () {
    $user = User::query()->create([
        'email' => 'ok@example.test',
        'password' => Hash::make('my-secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    expect(userRepo()->checkPassword($user, 'my-secret'))->toBeTrue();
});

test('checkPassword returns false for wrong password', function () {
    $user = User::query()->create([
        'email' => 'wrong@example.test',
        'password' => Hash::make('correct-one'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    expect(userRepo()->checkPassword($user, 'not-correct'))->toBeFalse();
});

test('checkPassword works with different hashes of same password', function () {
    $password = 'same-pass';

    $user1 = User::query()->create([
        'email' => 'u1@example.test',
        'password' => Hash::make($password),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $user2 = User::query()->create([
        'email' => 'u2@example.test',
        'password' => Hash::make($password), // different salt → different hash
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    expect(userRepo()->checkPassword($user1, $password))->toBeTrue()
        ->and(userRepo()->checkPassword($user2, $password))->toBeTrue();
});

test('checkPassword returns false if hash is invalid or empty', function () {
    $user = User::query()->create([
        'email' => 'bad@example.test',
        'password' => 'not-a-hash',
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    expect(userRepo()->checkPassword($user, 'anything'))->toBeFalse();
});

test('checkPassword does not modify user instance or db', function () {
    $user = User::query()->create([
        'email' => 'immu@example.test',
        'password' => Hash::make('secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    userRepo()->checkPassword($user, 'secret');

    // User must still exist unchanged in DB
    $fresh = User::find($user->id);
    expect($fresh->email)->toBe('immu@example.test')
        ->and(Hash::check('secret', $fresh->password))->toBeTrue();
});
