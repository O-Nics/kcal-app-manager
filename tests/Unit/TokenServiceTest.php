<?php

use App\Features\Auth\Services\TokenPair;
use App\Features\Auth\Services\TokenService;
use App\Models\User;
use App\Support\Enums\TokenAbility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\PersonalAccessToken;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Valeurs par défaut (tu peux les changer par config si besoin)
    config([
        'sanctum.ac_expiration' => 30,
        'sanctum.rt_expiration' => 60 * 24 * 30,
    ]);

    // Temps figé pour des expirations déterministes
    Carbon::setTestNow(Carbon::create(2025, 8, 26, 12, 0, 0));
});

it('issues access & refresh tokens with correct names, abilities and expirations', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);
    $pair = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);

    // DTO
    expect($pair)->toBeInstanceOf(TokenPair::class)
        ->and($pair->accessPlain())->toBeString()->not->toBeEmpty()
        ->and($pair->refreshPlain())->toBeString()->not->toBeEmpty()
        ->and($pair->accessId())->toBeInt()
        ->and($pair->refreshId())->toBeInt();

    // DB
    $access  = PersonalAccessToken::query()->find($pair->accessId());
    $refresh = PersonalAccessToken::query()->find($pair->refreshId());

    expect($access)->not->toBeNull()
        ->and($access->name)->toBe('access_token')
        ->and($access->tokenable_id)->toBe($user->id)
        ->and($access->abilities)->toMatchArray([TokenAbility::ACCESS_TOKEN->value])
        ->and($access->expires_at->equalTo(now()->copy()->addMinutes(config('sanctum.ac_expiration'))))->toBeTrue()
        ->and($refresh)->not->toBeNull()
        ->and($refresh->name)->toBe('refresh_token')
        ->and($refresh->tokenable_id)->toBe($user->id)
        ->and($refresh->abilities)->toMatchArray([TokenAbility::REFRESH_TOKEN->value])
        ->and($refresh->expires_at->equalTo(now()->copy()->addMinutes(config('sanctum.rt_expiration'))))->toBeTrue();

});
it('issues tokens with multiple abilities', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);
    $pair = $svc->issueFor(
        $user,
        [TokenAbility::ACCESS_TOKEN->value, 'another-ability'],
        [TokenAbility::REFRESH_TOKEN->value, 'yet-another-ability'],
    );

    // DB
    $access  = PersonalAccessToken::query()->find($pair->accessId());
    $refresh = PersonalAccessToken::query()->find($pair->refreshId());

    expect($access)->not->toBeNull()
        ->and($access->abilities)->toMatchArray([TokenAbility::ACCESS_TOKEN->value, 'another-ability'])
        ->and($refresh)->not->toBeNull()
        ->and($refresh->abilities)->toMatchArray([TokenAbility::REFRESH_TOKEN->value, 'yet-another-ability']);
});

it('issues tokens without abilities', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);
    $pair = $svc->issueFor($user, [], []);

    // DB
    $access  = PersonalAccessToken::query()->find($pair->accessId());
    $refresh = PersonalAccessToken::query()->find($pair->refreshId());

    expect($access)->not->toBeNull()
        ->and($access->abilities)->toMatchArray([])
        ->and($refresh)->not->toBeNull()
        ->and($refresh->abilities)->toMatchArray([]);
});

it('issues tokens with correct user association', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $svc = app(TokenService::class);
    $pair1 = $svc->issueFor($user1, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);
    $pair2 = $svc->issueFor($user2, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);

    // DB
    $access1  = PersonalAccessToken::query()->find($pair1->accessId());
    $refresh1 = PersonalAccessToken::query()->find($pair1->refreshId());
    $access2  = PersonalAccessToken::query()->find($pair2->accessId());
    $refresh2 = PersonalAccessToken::query()->find($pair2->refreshId());

    expect($access1)->not->toBeNull()
        ->and($access1->tokenable_id)->toBe($user1->id)
        ->and($refresh1)->not->toBeNull()
        ->and($refresh1->tokenable_id)->toBe($user1->id)
        ->and($access2)->not->toBeNull()
        ->and($access2->tokenable_id)->toBe($user2->id)
        ->and($refresh2)->not->toBeNull()
        ->and($refresh2->tokenable_id)->toBe($user2->id);
});

it('issues tokens with distinct plain text values', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);
    $pair1 = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);
    $pair2 = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);

    expect($pair1->accessPlain())->not->toBe($pair2->accessPlain())
        ->and($pair1->refreshPlain())->not->toBe($pair2->refreshPlain());
});

it('issues tokens with distinct IDs', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);
    $pair1 = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);
    $pair2 = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);

    expect($pair1->accessId())->not->toBe($pair2->accessId())
        ->and($pair1->refreshId())->not->toBe($pair2->refreshId());
});

it('tokens expire at correct times based on config', function () {
    $user = User::factory()->create();

    // Changer les configurations d'expiration
    config([
        'sanctum.ac_expiration' => 45,      // 45 minutes
        'sanctum.rt_expiration' => 60 * 24, // 24 heures
    ]);

    $svc = app(TokenService::class);
    $pair = $svc->issueFor($user, [TokenAbility::ACCESS_TOKEN->value], [TokenAbility::REFRESH_TOKEN->value]);

    // DB
    $access  = PersonalAccessToken::query()->find($pair->accessId());
    $refresh = PersonalAccessToken::query()->find($pair->refreshId());

    expect($access)->not->toBeNull()
        ->and($access->expires_at->equalTo(now()->copy()->addMinutes(45)))->toBeTrue()
        ->and($refresh)->not->toBeNull()
        ->and($refresh->expires_at->equalTo(now()->copy()->addMinutes(60 * 24)))->toBeTrue();
});

it('allows retrieval via findToken using plain tokens', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    $pair = $svc->issueFor($user, ['read'], ['refresh']);

    $accessDb  = PersonalAccessToken::findToken($pair->accessPlain());
    $refreshDb = PersonalAccessToken::findToken($pair->refreshPlain());

    expect($accessDb)->not->toBeNull()
        ->and($accessDb->tokenable_id)->toBe($user->id)
        ->and($refreshDb)->not->toBeNull()
        ->and($refreshDb->tokenable_id)->toBe($user->id);

});
