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

function repo(): PasswordResetRepository {
    return app(PasswordResetRepositoryEloquent::class);
}

function makeForgotDto(string $email = 'user@example.com'): ForgotPasswordData {
    return ForgotPasswordData::from([
        'email' => $email,
    ]);
}

it('stores a password reset with random 200-char token and timestamped created_at', function () {
    $dto = makeForgotDto('alpha@example.com');

    $record = repo()->storePasswordReset($dto);

    expect($record)->toBeInstanceOf(PasswordReset::class)
        ->and($record->exists)->toBeTrue()
        ->and($record->email)->toBe('alpha@example.com')
        ->and($record->token)->toBeString()->toHaveLength(200)
        ->and($record->created_at)->not->toBeNull()
        ->and($record->created_at->equalTo(now()))->toBeTrue();
});

it('generates different tokens on successive calls for the same email', function () {
    $dto = makeForgotDto('beta@example.com');

    $r1 = repo()->storePasswordReset($dto);
    $r2 = repo()->storePasswordReset($dto);

    expect($r1->token)->not->toBe($r2->token);
});

it('finds by exact email and token and returns null on mismatch', function () {
    $dto = makeForgotDto('findme@example.com');
    $saved = repo()->storePasswordReset($dto);

    // Found
    $found = repo()->findByEmailAndToken('findme@example.com', $saved->token);
    expect($found)->not->toBeNull()
        ->and($found->id)->toEqual($saved->id);

    // Wrong email
    $notFound1 = repo()->findByEmailAndToken('other@example.com', $saved->token);
    expect($notFound1)->toBeNull();

    // Wrong token
    $notFound2 = repo()->findByEmailAndToken('findme@example.com', str_repeat('x', 200));
    expect($notFound2)->toBeNull();
});

it('remove() deletes only the matching password reset row', function () {
    $a = repo()->storePasswordReset(makeForgotDto('rm_a@example.com'));
    $b = repo()->storePasswordReset(makeForgotDto('rm_b@example.com'));

    // Sanity: 2 rows
    expect(PasswordReset::count())->toBe(2);

    // Remove A only
    repo()->remove($a);

    expect(PasswordReset::count())->toBe(1)
        ->and(PasswordReset::first()->id)->toEqual($b->id);
});

it('supports multiple resets for the same email and remove() only deletes the exact token/email pair', function () {
    $dto = makeForgotDto('multi@example.com');
    $r1 = repo()->storePasswordReset($dto);
    $r2 = repo()->storePasswordReset($dto);

    expect(PasswordReset::where('email', 'multi@example.com')->count())->toBe(2);

    repo()->remove($r1);

    $left = PasswordReset::where('email', 'multi@example.com')->get();
    expect($left)->toHaveCount(1)
        ->and($left->first()->id)->toEqual($r2->id);
});

it('persists created_at as now() even if database default is different', function () {
    $dto = makeForgotDto('ts@example.com');
    $r = repo()->storePasswordReset($dto);

    // Vérifie que la colonne est bien égale à Carbon::now() (fixée en test)
    expect($r->created_at->equalTo(now()))->toBeTrue();
});

it('can handle very long email addresses', function () {
    $longEmail = str_repeat('a', 240) . '@example.com'; // 252 chars total
    $dto = makeForgotDto($longEmail);
    $record = repo()->storePasswordReset($dto);

    expect($record->email)->toBe($longEmail)
        ->and($record->token)->toHaveLength(200);
});

it('findByEmailAndToken is case-sensitive on email by default', function () {
    $dto = makeForgotDto('casecheck@example.com');
    $saved = repo()->storePasswordReset($dto);

    $foundLower = repo()->findByEmailAndToken('CASECHECK@example.com', $saved->token);
    expect($foundLower)->toBeNull();
});

it('remove() is idempotent (calling twice does not throw and leaves row absent)', function () {
    $dto = makeForgotDto('idempotent@example.com');
    $r = repo()->storePasswordReset($dto);

    repo()->remove($r);
    expect(PasswordReset::count())->toBe(0);

    // Call again
    repo()->remove($r);
    expect(PasswordReset::count())->toBe(0);
});

it('storePasswordReset throws when database fails', function () {
    // Simule une erreur de DB avec un mock du modèle
    $this->expectException(Throwable::class);

    $mock = Mockery::mock(\App\Support\Repositories\PasswordReset\PasswordResetRepositoryEloquent::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $dto = makeForgotDto('fail@example.com');
    // Forcer saveOrFail à lancer une exception
    $mock->shouldReceive('storePasswordReset')->once()->andThrow(new \Exception('DB fail'));

    $mock->storePasswordReset($dto);
});

it('stores multiple records and findByEmailAndToken returns the correct one', function () {
    $dto = makeForgotDto('multi2@example.com');
    $r1 = repo()->storePasswordReset($dto);
    $r2 = repo()->storePasswordReset($dto);

    $found = repo()->findByEmailAndToken($dto->email, $r2->token);
    expect($found->id)->toEqual($r2->id)
        ->and($found->token)->toBe($r2->token);
});

it('tokens are unique across different emails', function () {
    $a = repo()->storePasswordReset(makeForgotDto('uniq1@example.com'));
    $b = repo()->storePasswordReset(makeForgotDto('uniq2@example.com'));

    expect($a->token)->not->toBe($b->token);
});

it('created_at respects the frozen test time', function () {
    $dto = makeForgotDto('frozen@example.com');
    $r = repo()->storePasswordReset($dto);

    expect($r->created_at->equalTo(Carbon::getTestNow()))->toBeTrue();
});
