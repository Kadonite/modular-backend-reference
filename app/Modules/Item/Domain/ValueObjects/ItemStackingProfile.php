<?php

declare(strict_types=1);

namespace App\Modules\Item\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ItemStackingProfile
{
    public function __construct(
        private ItemDefinitionId $itemDefinitionId,
        private int $maxStackSize,
        private StackCompatibilityKey $compatibilityKey
    ) {
        if ($this->maxStackSize < 1) {
            throw new InvalidArgumentException(
                'Maximum stack size must be greater than zero.'
            );
        }
    }

    public function itemDefinitionId(): ItemDefinitionId
    {
        return $this->itemDefinitionId;
    }

    public function maxStackSize(): int
    {
        return $this->maxStackSize;
    }

    public function compatibilityKey(): StackCompatibilityKey
    {
        return $this->compatibilityKey;
    }

    public function isStackable(): bool
    {
        return $this->maxStackSize > 1;
    }

    public function equals(self $other): bool
    {
        return $this->itemDefinitionId->equals($other->itemDefinitionId)
            && $this->maxStackSize === $other->maxStackSize
            && $this->compatibilityKey->equals($other->compatibilityKey);
    }
}
