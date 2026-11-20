<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

final readonly class ItemMovedWithinInventory implements DomainEvent
{
    public function __construct(
        public InventoryId $inventoryId,
        public int $sourceSlotIndex,
        public int $destinationSlotIndex,
    ) {}
}
