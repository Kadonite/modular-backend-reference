<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\ValueObjects;

use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;

final readonly class ItemStack
{
    public function __construct(
        private ItemDefinitionId $itemDefinitionId,
        private ItemQuantity $quantity,
        private StackCompatibilityKey $compatibilityKey
    ) {}

    public function itemDefinitionId(): ItemDefinitionId
    {
        return $this->itemDefinitionId;
    }

    public function quantity(): ItemQuantity
    {
        return $this->quantity;
    }

    public function compatibilityKey(): StackCompatibilityKey
    {
        return $this->compatibilityKey;
    }

    public function equals(self $other): bool
    {
        return $this->itemDefinitionId->equals($other->itemDefinitionId)
            && $this->quantity->equals($other->quantity)
            && $this->compatibilityKey->equals($other->compatibilityKey);
    }
}
