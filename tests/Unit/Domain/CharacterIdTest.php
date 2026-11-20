<?php

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use InvalidArgumentException;

it('stores a character identifier', function () {
    $id = new CharacterId('character-123');

    expect($id->value())->toBe('character-123');
});

it('considers identifiers with the same value equal', function () {
    $first = new CharacterId('character-123');
    $second = new CharacterId('character-123');

    expect($first->equals($second))->toBeTrue();
});

it('considers identifiers with different values unequal', function () {
    $first = new CharacterId('character-123');
    $second = new CharacterId('character-456');

    expect($first->equals($second))->toBeFalse();
});

it('rejects an empty identifier', function () {
    new CharacterId('');
})->throws(InvalidArgumentException::class);

it('rejects a whitespace-only identifier', function () {
    new CharacterId('   ');
})->throws(InvalidArgumentException::class);
