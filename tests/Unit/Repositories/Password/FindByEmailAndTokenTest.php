<?php

use App\Features\Auth\Data\ForgotPasswordData;
use App\Models\PasswordReset;
use App\Support\Repositories\PasswordReset\PasswordResetRepository;
use App\Support\Repositories\PasswordReset\PasswordResetRepositoryEloquent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2025, 8, 28, 12, 0, 0));
});


// -----------------------
// findByEmailAndToken()
// -----------------------

test('findByEmailAndToken returns reset when email and token match', function () {

    $reset = PasswordReset::query()->create([
        'email' => 'john@example.test',
        'token' => 'tok-abc',
        'created_at' => now(),
    ]);

    $found = passwordResetRepo()->findByEmailAndToken('john@example.test', 'tok-abc');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($reset->id);
});


test('findByEmailAndToken returns null when email does not match', function () {

    PasswordReset::query()->create([
        'email' => 'real@example.test',
        'token' => 'tok-real',
        'created_at' => now(),
    ]);

    $found = passwordResetRepo()->findByEmailAndToken('fake@example.test', 'tok-real');

    expect($found)->toBeNull();
});


test('findByEmailAndToken returns null when token does not match', function () {

    PasswordReset::query()->create([
        'email' => 'real2@example.test',
        'token' => 'tok-real2',
        'created_at' => now(),
    ]);

    $found = passwordResetRepo()->findByEmailAndToken('real2@example.test', 'wrong');

    expect($found)->toBeNull();
});


test('findByEmailAndToken discriminates by both email and token', function () {

    $target = PasswordReset::query()->create([
        'email' => 'multi@example.test',
        'token' => 'tok-target',
        'created_at' => now(),
    ]);
    PasswordReset::query()->create([
        'email' => 'multi@example.test',
        'token' => 'tok-other',
        'created_at' => now(),
    ]);

    $found = passwordResetRepo()->findByEmailAndToken('multi@example.test', 'tok-target');

    expect($found->id)->toBe($target->id);
});


test('findByEmailAndToken returns null when table is empty', function () {

    $found = passwordResetRepo()->findByEmailAndToken('none@example.test', 'tok');

    expect($found)->toBeNull();
});


test('findByEmailAndToken is case sensitive on both fields', function () {

    PasswordReset::query()->create([
        'email' => 'Case@Test.com',
        'token' => 'TokCase',
        'created_at' => now(),
    ]);

    expect(passwordResetRepo()->findByEmailAndToken('case@test.com', 'TokCase'))->toBeNull()
        ->and(passwordResetRepo()->findByEmailAndToken('Case@Test.com', 'tokcase'))->toBeNull()
        ->and(passwordResetRepo()->findByEmailAndToken('Case@Test.com', 'TokCase'))->not->toBeNull();
});



