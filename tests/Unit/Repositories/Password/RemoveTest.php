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
    // Fige l'heure pour des assertions déterministes
    Carbon::setTestNow(Carbon::create(2025, 8, 28, 12, 0, 0));
});
// -----------------------
// remove()
// -----------------------

test('remove deletes the exact matching password reset', function () {
    $reset = PasswordReset::query()->create([
        'email' => 'remove@example.test',
        'token' => 'tok123',
        'created_at' => now(),
    ]);

    expect(PasswordReset::query()->count())->toBe(1);

    passwordResetRepo()->remove($reset);

    expect(PasswordReset::query()->count())->toBe(0);
});


test('remove does not delete other tokens with same email', function () {
    $target = PasswordReset::query()->create([
        'email' => 'same@example.test',
        'token' => 'delete-me',
        'created_at' => now(),
    ]);

    $other = PasswordReset::query()->create([
        'email' => 'same@example.test',
        'token' => 'keep-me',
        'created_at' => now(),
    ]);

    passwordResetRepo()->remove($target);

    expect(PasswordReset::query()->pluck('token')->values()->all())->toBe(['keep-me']);
});


test('remove does not delete if token does not match', function () {
    $reset = PasswordReset::query()->create([
        'email' => 'nomatch@example.test',
        'token' => 'abc123',
        'created_at' => now(),
    ]);

    // Try to remove with wrong token
    $fake = new PasswordReset([
        'email' => 'nomatch@example.test',
        'token' => 'wrong',
    ]);

    passwordResetRepo()->remove($fake);

    expect(PasswordReset::query()->count())->toBe(1);
});


test('remove is idempotent (calling twice does not error)', function () {
    $reset = PasswordReset::query()->create([
        'email' => 'idem@example.test',
        'token' => 'tok1',
        'created_at' => now(),
    ]);

    passwordResetRepo()->remove($reset);
    passwordResetRepo()->remove($reset);
    expect(PasswordReset::query()->count())->toBe(0);
});


test('remove only deletes matching email+token pair', function () {
    $reset1 = PasswordReset::query()->create([
        'email' => 'user1@example.test',
        'token' => 'tok1',
        'created_at' => now(),
    ]);
    $reset2 = PasswordReset::query()->create([
        'email' => 'user2@example.test',
        'token' => 'tok1',
        'created_at' => now(),
    ]);

    passwordResetRepo()->remove($reset1);

    expect(PasswordReset::query()->pluck('email')->values()->all())->toBe(['user2@example.test']);
});


test('remove leaves table untouched when password reset not found', function () {
    $existing = PasswordReset::query()->create([
        'email' => 'exists@example.test',
        'token' => 'tok-exists',
        'created_at' => now(),
    ]);

    $fake = new PasswordReset([
        'email' => 'ghost@example.test',
        'token' => 'tok-ghost',
    ]);

    passwordResetRepo()->remove($fake);

    expect(PasswordReset::query()->count())->toBe(1);
});
