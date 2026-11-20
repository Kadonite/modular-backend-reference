<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Inventory\Application\Contracts\InventoryRepository;
use App\Modules\Inventory\Application\Enums\InventoryRepositoryError;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryRecord;
use App\Modules\Inventory\Infrastructure\Persistence\Mappers\InventoryMapper;
use App\Shared\Domain\Result;
use Illuminate\Support\Facades\DB;

final readonly class EloquentInventoryRepository implements InventoryRepository
{
    public function __construct(
        private InventoryMapper $mapper,
    ) {}

    public function findById(InventoryId $inventoryId): ?Inventory
    {
        $record = InventoryRecord::query()
            ->find($inventoryId->value());

        if ($record === null) {
            return null;
        }

        return $this->mapper->toDomain($record);
    }

    /**
     * @return Result<null, InventoryRepositoryError>
     */
    public function save(
        Inventory $inventory,
        int $expectedVersion,
    ): Result {
        $saved = DB::transaction(function () use (
            $inventory,
            $expectedVersion,
        ): bool {
            $attributes = $this->mapper->toRecordAttributes($inventory);

            unset($attributes['id']);

            $updated = InventoryRecord::query()
                ->whereKey($inventory->id()->value())
                ->where('version', $expectedVersion)
                ->update($attributes);

            if ($updated !== 1) {
                return false;
            }

            DB::table('inventory_slots')
                ->where('inventory_id', $inventory->id()->value())
                ->delete();

            $slotAttributes = $this->mapper
                ->toSlotRecordAttributes($inventory);

            if ($slotAttributes !== []) {
                DB::table('inventory_slots')->insert($slotAttributes);
            }

            return true;
        });

        if (! $saved) {
            return Result::failure(
                InventoryRepositoryError::ConcurrencyConflict,
            );
        }

        return Result::success(null);
    }
}
