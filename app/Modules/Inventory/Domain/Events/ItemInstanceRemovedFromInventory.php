<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Shared\Domain\DomainEvent;

final readonly class ItemInstanceRemovedFromInventory implements DomainEvent
{
    public function __construct(
        public InventoryId $inventoryId,
        public ItemInstanceId $itemInstanceId,
        public int $slotIndex,
    ) {}
}
