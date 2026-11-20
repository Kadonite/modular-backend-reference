<?php

use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemStackingProfile;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use InvalidArgumentException;

it('stores item stacking profile details', function () {
    $definitionId = new ItemDefinitionId('iron-ore');
    $compatibilityKey = new StackCompatibilityKey('standard');

    $profile = new ItemStackingProfile(
        $definitionId,
        100,
        $compatibilityKey
    );

    expect($profile->itemDefinitionId())->toBe($definitionId)
        ->and($profile->maxStackSize())->toBe(100)
        ->and($profile->compatibilityKey())->toBe($compatibilityKey);
});

it('identifies stackable items', function () {
    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    expect($profile->isStackable())->toBeTrue();
});

it('identifies non-stackable items', function () {
    $profile = new ItemStackingProfile(
        new ItemDefinitionId('iron-sword'),
        1,
        new StackCompatibilityKey('unique')
    );

    expect($profile->isStackable())->toBeFalse();
});

it('considers profiles with identical values equal', function () {
    $first = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    expect($first->equals($second))->toBeTrue();
});

it('considers profiles with different maximum stack sizes unequal', function () {
    $first = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        100,
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        50,
        new StackCompatibilityKey('standard')
    );

    expect($first->equals($second))->toBeFalse();
});

it('rejects zero maximum stack size', function () {
    new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        0,
        new StackCompatibilityKey('standard')
    );
})->throws(InvalidArgumentException::class);

it('rejects negative maximum stack size', function () {
    new ItemStackingProfile(
        new ItemDefinitionId('iron-ore'),
        -1,
        new StackCompatibilityKey('standard')
    );
})->throws(InvalidArgumentException::class);
