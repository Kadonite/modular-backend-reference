<?php

declare(strict_types=1);

namespace App\Modules\Item\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ItemInstanceId
{
    public function __construct(
        private string $value
    ) {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException(
                'Item instance ID cannot be empty.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
