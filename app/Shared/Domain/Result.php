<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use BackedEnum;
use LogicException;

/**
 * @template-covariant TValue
 * @template-covariant TError of BackedEnum
 */
final readonly class Result
{
    /** @var TValue|null */
    private mixed $value;

    /** @var TError|null */
    private ?BackedEnum $error;

    private bool $successful;

    private function __construct(
        bool $successful,
        mixed $value,
        ?BackedEnum $error
    ) {
        $this->successful = $successful;
        $this->value = $value;
        $this->error = $error;
    }

    /**
     * @template TSuccess
     *
     * @param  TSuccess  $value
     * @return self<TSuccess, never>
     */
    public static function success(mixed $value): self
    {
        /** @var self<TSuccess, never> $result */
        $result = new self(true, $value, null);

        return $result;
    }

    /**
     * @template TFailure of BackedEnum
     *
     * @param  TFailure  $error
     * @return self<never, TFailure>
     */
    public static function failure(BackedEnum $error): self
    {
        /** @var self<never, TFailure> $result */
        $result = new self(false, null, $error);

        return $result;
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function isFailure(): bool
    {
        return ! $this->successful;
    }

    /**
     * @return TValue
     */
    public function value(): mixed
    {
        if (! $this->successful) {
            throw new LogicException(
                'Cannot retrieve a value from a failed result.'
            );
        }

        return $this->value;
    }

    /**
     * @return TError|null
     */
    public function error(): ?BackedEnum
    {
        return $this->error;
    }
}
