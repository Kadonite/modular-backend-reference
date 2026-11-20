<?php

use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use InvalidArgumentException;

it('stores an item definition identifier', function () {
    $id = new ItemDefinitionId('iron-ore');

    expect($id->value())->toBe('iron-ore');
});

it('considers identifiers with the same value equal', function () {
    $first = new ItemDefinitionId('iron-ore');
    $second = new ItemDefinitionId('iron-ore');

    expect($first->equals($second))->toBeTrue();
});

it('considers identifiers with different values unequal', function () {
    $first = new ItemDefinitionId('iron-ore');
    $second = new ItemDefinitionId('copper-ore');

    expect($first->equals($second))->toBeFalse();
});

it('rejects an empty identifier', function () {
    new ItemDefinitionId('');
})->throws(InvalidArgumentException::class);

it('rejects a whitespace-only identifier', function () {
    new ItemDefinitionId('   ');
})->throws(InvalidArgumentException::class);
