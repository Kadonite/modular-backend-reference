<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Events;

use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\DomainEvent;

final readonly class InventoryCapacityIncreased implements DomainEvent
{
    public function __construct(
        public InventoryId $inventoryId,
        public int $previousCapacity,
        public int $newCapacity,
    ) {}
}
