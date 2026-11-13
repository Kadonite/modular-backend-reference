
<?php

use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use InvalidArgumentException;

it('stores a positive quantity', function () {
    $quantity = new ItemQuantity(10);

    expect($quantity->value())->toBe(10);
});

it('considers quantities with the same value equal', function () {
    $first = new ItemQuantity(10);
    $second = new ItemQuantity(10);

    expect($first->equals($second))->toBeTrue();
});

it('considers quantities with different values unequal', function () {
    $first = new ItemQuantity(10);
    $second = new ItemQuantity(5);

    expect($first->equals($second))->toBeFalse();
});

it('rejects a zero quantity', function () {
    expect(fn () => new ItemQuantity(0))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a negative quantity', function () {
    expect(fn () => new ItemQuantity(-1))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts the minimum valid quantity', function () {
    $quantity = new ItemQuantity(1);

    expect($quantity->value())->toBe(1);
});
