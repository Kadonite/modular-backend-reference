<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Contracts;

use App\Modules\Inventory\Application\Enums\InventoryRepositoryError;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Shared\Domain\Result;

interface InventoryRepository
{
    public function findById(
        InventoryId $inventoryId
    ): ?Inventory;

    /**
     * Persists the current state of an existing inventory aggregate.
     *
     * The expected version represents the version originally loaded
     * by the application, before any domain mutations.
     *
     * This operation does not create inventories.
     *
     * Persistence must be atomic. A concurrency conflict must leave
     * the persisted inventory unchanged.
     *
     * @return Result<null, InventoryRepositoryError>
     */
    public function save(
        Inventory $inventory,
        int $expectedVersion
    ): Result;
}
