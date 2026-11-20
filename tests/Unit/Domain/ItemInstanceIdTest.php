<?php

use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use InvalidArgumentException;

it('stores an item instance identifier', function () {
    $id = new ItemInstanceId('instance-123');

    expect($id->value())->toBe('instance-123');
});

it('considers identifiers with the same value equal', function () {
    $first = new ItemInstanceId('instance-123');
    $second = new ItemInstanceId('instance-123');

    expect($first->equals($second))->toBeTrue();
});

it('considers identifiers with different values unequal', function () {
    $first = new ItemInstanceId('instance-123');
    $second = new ItemInstanceId('instance-456');

    expect($first->equals($second))->toBeFalse();
});

it('rejects an empty identifier', function () {
    new ItemInstanceId('');
})->throws(InvalidArgumentException::class);

it('rejects a whitespace-only identifier', function () {
    new ItemInstanceId('   ');
})->throws(InvalidArgumentException::class);
