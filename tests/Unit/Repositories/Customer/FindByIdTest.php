<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// -------------------------------------------------
// findById()
// -------------------------------------------------

test('findById returns the matching customer by id', function () {
    $user = User::query()->create([
        'email' => 'owner@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $customer = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);

    $found = customerRepo()->findById((string) $customer->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($customer->id);
});

test('findById returns null when id does not exist', function () {
    $user = User::query()->create([
        'email' => 'someone@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    // create one to make sure table isn't empty
    $user->customers()->create([
        'is_owner' => false,
        'accept_marketing' => false,
    ]);

    $found = customerRepo()->findById('non-existent-id');

    expect($found)->toBeNull();
});

test('findById does not return a different customer', function () {
    $user = User::query()->create([
        'email' => 'multi@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $c1 = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);
    $c2 = $user->customers()->create([
        'is_owner' => false,
        'accept_marketing' => false,
    ]);

    $found = customerRepo()->findById((string) $c2->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($c2->id)
        ->and($found->id)->not->toBe($c1->id);
});

test('findById accepts string ids and works with UUID/ULID', function () {
    $user = User::query()->create([
        'email' => 'idtype@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $customer = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);

    // Ensure we pass a string explicitly
    $idAsString = (string) $customer->id;

    $found = customerRepo()->findById($idAsString);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe($idAsString);
});
