<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ItemQuantity
{
    public function __construct(
        private int $value
    ) {
        if ($this->value < 1) {
            throw new InvalidArgumentException(
                'Item quantity must be greater than zero.'
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
