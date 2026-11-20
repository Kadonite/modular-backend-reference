<?php

use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use InvalidArgumentException;

it('stores stack compatibility key', function () {
    $id = new StackCompatibilityKey('instance-123');

    expect($id->value())->toBe('instance-123');
});

it('considers identifiers with the same value equal', function () {
    $first = new StackCompatibilityKey('instance-123');
    $second = new StackCompatibilityKey('instance-123');

    expect($first->equals($second))->toBeTrue();
});

it('considers identifiers with different values unequal', function () {
    $first = new StackCompatibilityKey('instance-123');
    $second = new StackCompatibilityKey('instance-456');

    expect($first->equals($second))->toBeFalse();
});

it('rejects an empty identifier', function () {
    new StackCompatibilityKey('');
})->throws(InvalidArgumentException::class);

it('rejects a whitespace-only identifier', function () {
    new StackCompatibilityKey('   ');
})->throws(InvalidArgumentException::class);
