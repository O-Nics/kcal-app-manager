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
// storePasswordReset()
// -----------------------

test('stores password reset with given email and 200-char token', function () {
    $dto = makeForgotDto('john@example.test');

    $reset = passwordResetRepo()->storePasswordReset($dto);

    expect($reset->email)->toBe('john@example.test')
        ->and($reset->token)->toBeString()
        ->and(strlen($reset->token))->toBe(200)
        ->and($reset->created_at->equalTo(now()))->toBeTrue();
});


test('generates unique token on each call', function () {
    $dto = makeForgotDto('uniq@example.test');

    $a = passwordResetRepo()->storePasswordReset($dto);
    $b = passwordResetRepo()->storePasswordReset($dto);

    expect($a->token)->not->toBe($b->token);
});


test('persists exact email casing as provided', function () {
    $dto = makeForgotDto('MiXeD.Case+tag@Example.TEST');

    $reset = passwordResetRepo()->storePasswordReset($dto);

    expect($reset->email)->toBe('MiXeD.Case+tag@Example.TEST');
});


test('allows multiple resets for the same email (no upsert)', function () {
    $dto = makeForgotDto('multi@example.test');

    passwordResetRepo()->storePasswordReset($dto);
    passwordResetRepo()->storePasswordReset($dto);

    expect(PasswordReset::query()->where('email', 'multi@example.test')->count())->toBe(2);
});


test('token is strictly alphanumeric and length 200', function () {
    $dto = makeForgotDto('format@example.test');

    $reset = passwordResetRepo()->storePasswordReset($dto);

    expect($reset->token)->toMatch('/^[A-Za-z0-9]{200}$/');
});


test('created_at is set to now()', function () {
    $dto = makeForgotDto('time@example.test');

    $reset = passwordResetRepo()->storePasswordReset($dto);

    expect($reset->created_at->equalTo(now()))->toBeTrue();
});
