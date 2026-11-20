<?php

use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;

it('stores item stack details', function () {
    $definitionId = new ItemDefinitionId('iron-ore');
    $quantity = new ItemQuantity(25);
    $compatibilityKey = new StackCompatibilityKey('standard');

    $stack = new ItemStack(
        $definitionId,
        $quantity,
        $compatibilityKey
    );

    expect($stack->itemDefinitionId())->toBe($definitionId)
        ->and($stack->quantity())->toBe($quantity)
        ->and($stack->compatibilityKey())->toBe($compatibilityKey);
});

it('considers stacks with identical values equal', function () {
    $first = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    expect($first->equals($second))->toBeTrue();
});

it('considers stacks with different definitions unequal', function () {
    $first = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStack(
        new ItemDefinitionId('copper-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    expect($first->equals($second))->toBeFalse();
});

it('considers stacks with different quantities unequal', function () {
    $first = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(30),
        new StackCompatibilityKey('standard')
    );

    expect($first->equals($second))->toBeFalse();
});

it('considers stacks with different compatibility keys unequal', function () {
    $first = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('standard')
    );

    $second = new ItemStack(
        new ItemDefinitionId('iron-ore'),
        new ItemQuantity(25),
        new StackCompatibilityKey('enchanted')
    );

    expect($first->equals($second))->toBeFalse();
});
