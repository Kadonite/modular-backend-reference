<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use App\Shared\Domain\DomainEvent;

final readonly class ItemsRemovedFromInventory implements DomainEvent
{
    /**
     * @param  list<int>  $slotIndexes
     */
    public function __construct(
        public InventoryId $inventoryId,
        public ItemDefinitionId $itemDefinitionId,
        public StackCompatibilityKey $compatibilityKey,
        public int $quantity,
        public array $slotIndexes,
    ) {}
}
