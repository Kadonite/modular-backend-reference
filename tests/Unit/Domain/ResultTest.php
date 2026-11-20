<?php

use App\Shared\Domain\Result;
use LogicException;

enum ResultTestError: string
{
    case Example = 'example';
}

it('creates a successful result containing a value', function () {
    $result = Result::success(42);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->isFailure())->toBeFalse()
        ->and($result->value())->toBe(42)
        ->and($result->error())->toBeNull();
});

it('creates a failed result containing an error', function () {
    $result = Result::failure(ResultTestError::Example);

    expect($result->isFailure())->toBeTrue()
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->error())->toBe(ResultTestError::Example);
});

it('prevents retrieving a value from a failed result', function () {
    $result = Result::failure(ResultTestError::Example);

    expect(fn () => $result->value())
        ->toThrow(LogicException::class);
});
