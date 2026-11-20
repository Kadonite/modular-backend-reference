<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Aggregates;

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Domain\Entities\InventorySlot;
use App\Modules\Inventory\Domain\Enums\InventoryError;
use App\Modules\Inventory\Domain\Events\InventoryCapacityIncreased;
use App\Modules\Inventory\Domain\Events\InventorySlotsSwapped;
use App\Modules\Inventory\Domain\Events\ItemInstanceAddedToInventory;
use App\Modules\Inventory\Domain\Events\ItemInstanceRemovedFromInventory;
use App\Modules\Inventory\Domain\Events\ItemMovedWithinInventory;
use App\Modules\Inventory\Domain\Events\ItemsAddedToInventory;
use App\Modules\Inventory\Domain\Events\ItemsRemovedFromInventory;
use App\Modules\Inventory\Domain\Events\ItemStacksMerged;
use App\Modules\Inventory\Domain\Events\ItemStackSplit;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\ItemStackingProfile;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\Result;
use InvalidArgumentException;

final class Inventory
{
    /**
     * @var array<int, InventorySlot>
     */
    private array $slots = [];

    private int $version = 0;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    public function __construct(
        private readonly InventoryId $id,
        private readonly CharacterId $characterId,
        int $capacity
    ) {
        if ($capacity < 1) {
            throw new InvalidArgumentException(
                'Inventory capacity must be greater than zero.'
            );
        }

        for ($index = 0; $index < $capacity; $index++) {
            $this->slots[$index] = new InventorySlot($index);
        }
    }

    public function id(): InventoryId
    {
        return $this->id;
    }

    public function characterId(): CharacterId
    {
        return $this->characterId;
    }

    public function capacity(): int
    {
        return count($this->slots);
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return list<InventorySlot>
     */
    public function slots(): array
    {
        return array_values($this->slots);
    }

    public function slotAt(int $index): ?InventorySlot
    {
        return $this->slots[$index] ?? null;
    }

    public function increaseCapacity(int $additionalSlots): void
    {
        if ($additionalSlots < 1) {
            throw new InvalidArgumentException(
                'Additional capacity must be greater than zero.',
            );
        }

        $previousCapacity = $this->capacity();

        for ($i = 0; $i < $additionalSlots; $i++) {
            $this->slots[] = new InventorySlot(
                $previousCapacity + $i,
                null,
            );
        }

        $this->version++;

        $this->domainEvents[] = new InventoryCapacityIncreased(
            $this->id,
            $previousCapacity,
            $this->capacity(),
        );
    }

    /**
     * Adds an individual item instance to the first available slot.
     *
     * @return Result<int, InventoryError>
     */
    public function addItemInstance(ItemInstanceId $itemInstanceId): Result
    {
        // Prevent the same item instance from occupying multiple slots.
        foreach ($this->slots as $slot) {
            $existingItemInstanceId = $slot->itemInstanceId();

            if ($existingItemInstanceId?->equals($itemInstanceId)) {
                return Result::failure(
                    InventoryError::ItemInstanceAlreadyPresent
                );
            }
        }

        // Find the first available slot.
        foreach ($this->slots as $index => $slot) {
            if ($slot->isEmpty()) {
                $this->slots[$index] = new InventorySlot(
                    $index,
                    $itemInstanceId
                );

                $this->version++;

                $this->domainEvents[] = new ItemInstanceAddedToInventory(
                    $this->id,
                    $itemInstanceId,
                    $index,
                );

                return Result::success($index);
            }
        }

        return Result::failure(InventoryError::InventoryFull);
    }

    /**
     * Adds a quantity of stackable items to the inventory.
     *
     * Compatible partial stacks are filled before empty slots are used.
     * The operation either adds the complete quantity or makes no changes.
     *
     * @return Result<list<int>, InventoryError>
     */
    public function addItem(
        ItemQuantity $quantity,
        ItemStackingProfile $stackingProfile
    ): Result {
        if (! $stackingProfile->isStackable()) {
            return Result::failure(
                InventoryError::ItemNotStackable
            );
        }

        $remaining = $quantity->value();
        $maxStackSize = $stackingProfile->maxStackSize();
        $itemDefinitionId = $stackingProfile->itemDefinitionId();
        $compatibilityKey = $stackingProfile->compatibilityKey();

        /** @var array<int, ItemStack> $plannedChanges */
        $plannedChanges = [];

        // Phase 1: Fill compatible existing partial stacks.
        foreach ($this->slots as $index => $slot) {
            $existingStack = $slot->stack();

            if ($existingStack === null) {
                continue;
            }

            $sameDefinition = $existingStack
                ->itemDefinitionId()
                ->equals($itemDefinitionId);

            $sameCompatibility = $existingStack
                ->compatibilityKey()
                ->equals($compatibilityKey);

            if (! $sameDefinition || ! $sameCompatibility) {
                continue;
            }

            $existingQuantity = $existingStack->quantity()->value();

            $available = $maxStackSize - $existingQuantity;

            if ($available <= 0) {
                continue;
            }

            $amountToAdd = min($available, $remaining);

            $plannedChanges[$index] = new ItemStack(
                $itemDefinitionId,
                new ItemQuantity($existingQuantity + $amountToAdd),
                $compatibilityKey
            );

            $remaining -= $amountToAdd;

            if ($remaining === 0) {
                break;
            }
        }

        // Phase 2: Use empty slots for any remaining quantity.
        if ($remaining > 0) {
            foreach ($this->slots as $index => $slot) {
                if (! $slot->isEmpty()) {
                    continue;
                }

                $amountToAdd = min($maxStackSize, $remaining);

                $plannedChanges[$index] = new ItemStack(
                    $itemDefinitionId,
                    new ItemQuantity($amountToAdd),
                    $compatibilityKey
                );

                $remaining -= $amountToAdd;

                if ($remaining === 0) {
                    break;
                }
            }
        }

        // Phase 3: Reject without mutating if the entire quantity cannot fit.
        if ($remaining > 0) {
            return Result::failure(
                InventoryError::InsufficientCapacity
            );
        }

        // Phase 4: Apply the planned changes.
        /** @var list<int> $affectedSlotIndexes */
        $affectedSlotIndexes = [];

        foreach ($plannedChanges as $index => $stack) {
            $this->slots[$index] = new InventorySlot($index, $stack);

            $affectedSlotIndexes[] = $index;
        }

        $this->version++;

        $this->domainEvents[] = new ItemsAddedToInventory(
            inventoryId: $this->id,
            itemDefinitionId: $stackingProfile->itemDefinitionId(),
            compatibilityKey: $stackingProfile->compatibilityKey(),
            quantity: $quantity->value(),
            slotIndexes: $affectedSlotIndexes,
        );

        return Result::success($affectedSlotIndexes);
    }

    /**
     * Removes a quantity of items matching the supplied definition
     * and compatibility key.
     *
     * Matching stacks are consumed in ascending slot-index order.
     * The operation either removes the complete quantity or makes no changes.
     *
     * @return Result<list<int>, InventoryError>
     */
    public function removeItem(
        ItemDefinitionId $itemDefinitionId,
        StackCompatibilityKey $compatibilityKey,
        ItemQuantity $quantity
    ): Result {
        $remaining = $quantity->value();

        /** @var array<int, ItemStack|null> $plannedChanges */
        $plannedChanges = [];

        // Phase 1: Plan removals from matching stacks.
        foreach ($this->slots as $index => $slot) {
            $existingStack = $slot->stack();

            if ($existingStack === null) {
                continue;
            }

            $sameDefinition = $existingStack
                ->itemDefinitionId()
                ->equals($itemDefinitionId);

            $sameCompatibility = $existingStack
                ->compatibilityKey()
                ->equals($compatibilityKey);

            if (! $sameDefinition || ! $sameCompatibility) {
                continue;
            }

            $existingQuantity = $existingStack->quantity()->value();

            $amountToRemove = min($existingQuantity, $remaining);

            $newQuantity = $existingQuantity - $amountToRemove;

            $plannedChanges[$index] = $newQuantity === 0
                ? null
                : new ItemStack(
                    $existingStack->itemDefinitionId(),
                    new ItemQuantity($newQuantity),
                    $existingStack->compatibilityKey()
                );

            $remaining -= $amountToRemove;

            if ($remaining === 0) {
                break;
            }
        }

        // Phase 2: Reject without mutating if insufficient quantity exists.
        if ($remaining > 0) {
            return Result::failure(
                InventoryError::InsufficientQuantity
            );
        }

        // Phase 3: Apply the planned changes.
        /** @var list<int> $affectedSlotIndexes */
        $affectedSlotIndexes = [];

        foreach ($plannedChanges as $index => $stack) {
            $this->slots[$index] = new InventorySlot($index, $stack);

            $affectedSlotIndexes[] = $index;
        }

        // One successful business operation means one version increment.
        $this->version++;

        $this->domainEvents[] = new ItemsRemovedFromInventory(
            inventoryId: $this->id,
            itemDefinitionId: $itemDefinitionId,
            compatibilityKey: $compatibilityKey,
            quantity: $quantity->value(),
            slotIndexes: $affectedSlotIndexes,
        );

        return Result::success($affectedSlotIndexes);
    }

    public function removeItemInstance(ItemInstanceId $itemInstanceId): Result
    {
        foreach ($this->slots as $index => $slot) {
            if (
                $slot->itemInstanceId() === null
                || ! $slot->itemInstanceId()->equals($itemInstanceId)
            ) {
                continue;
            }

            $this->slots[$index] = new InventorySlot($index, null);

            $this->version++;

            $this->domainEvents[] = new ItemInstanceRemovedFromInventory(
                inventoryId: $this->id,
                itemInstanceId: $itemInstanceId,
                slotIndex: $index,
            );

            return Result::success($index);
        }

        return Result::failure(InventoryError::ItemInstanceNotFound);
    }

    public function occupiedSlots(): int
    {
        return count(array_filter(
            $this->slots,
            fn (InventorySlot $slot): bool => ! $slot->isEmpty(),
        ));
    }

    public function availableSlots(): int
    {
        return $this->capacity() - $this->occupiedSlots();
    }

    public function isEmpty(): bool
    {
        return $this->occupiedSlots() === 0;
    }

    public function moveToSlot(
        int $sourceIndex,
        int $destinationIndex,
    ): Result {
        if (
            $sourceIndex < 0 ||
            $sourceIndex >= $this->capacity() ||
            $destinationIndex < 0 ||
            $destinationIndex >= $this->capacity()
        ) {
            return Result::failure(InventoryError::InvalidSlotIndex);
        }

        if ($sourceIndex === $destinationIndex) {
            return Result::failure(InventoryError::SameSlot);
        }

        $sourceSlot = $this->slots[$sourceIndex];
        $destinationSlot = $this->slots[$destinationIndex];

        if ($sourceSlot->isEmpty()) {
            return Result::failure(InventoryError::SourceSlotEmpty);
        }

        if (! $destinationSlot->isEmpty()) {
            return Result::failure(InventoryError::DestinationSlotOccupied);
        }

        $this->slots[$destinationIndex] = new InventorySlot(
            $destinationIndex,
            $sourceSlot->content(),
        );

        $this->slots[$sourceIndex] = new InventorySlot($sourceIndex, null);

        $this->version++;

        $this->domainEvents[] = new ItemMovedWithinInventory(
            inventoryId: $this->id,
            sourceSlotIndex: $sourceIndex,
            destinationSlotIndex: $destinationIndex,
        );

        return Result::success([
            $sourceIndex,
            $destinationIndex,
        ]);
    }

    public function swapSlots(
        int $firstIndex,
        int $secondIndex,
    ): Result {
        if (
            $firstIndex < 0 ||
            $firstIndex >= $this->capacity() ||
            $secondIndex < 0 ||
            $secondIndex >= $this->capacity()
        ) {
            return Result::failure(InventoryError::InvalidSlotIndex);
        }

        if ($firstIndex === $secondIndex) {
            return Result::failure(InventoryError::SameSlot);
        }

        $firstSlot = $this->slots[$firstIndex];
        $secondSlot = $this->slots[$secondIndex];

        if ($firstSlot->isEmpty() && $secondSlot->isEmpty()) {
            return Result::failure(InventoryError::BothSlotsEmpty);
        }

        $this->slots[$firstIndex] = new InventorySlot(
            $firstIndex,
            $secondSlot->content(),
        );

        $this->slots[$secondIndex] = new InventorySlot(
            $secondIndex,
            $firstSlot->content(),
        );

        $this->version++;

        $this->domainEvents[] = new InventorySlotsSwapped(
            inventoryId: $this->id,
            firstSlotIndex: $firstIndex,
            secondSlotIndex: $secondIndex,
        );

        return Result::success([
            $firstIndex,
            $secondIndex,
        ]);
    }

    public function mergeStacks(
        int $sourceIndex,
        int $destinationIndex,
        ItemStackingProfile $stackingProfile,
    ): Result {
        if (
            $sourceIndex < 0 ||
            $sourceIndex >= $this->capacity() ||
            $destinationIndex < 0 ||
            $destinationIndex >= $this->capacity()
        ) {
            return Result::failure(InventoryError::InvalidSlotIndex);
        }

        if ($sourceIndex === $destinationIndex) {
            return Result::failure(InventoryError::SameSlot);
        }

        $sourceSlot = $this->slots[$sourceIndex];
        $destinationSlot = $this->slots[$destinationIndex];

        $sourceStack = $sourceSlot->stack();
        $destinationStack = $destinationSlot->stack();

        if ($sourceStack === null) {
            return Result::failure(
                InventoryError::SourceSlotDoesNotContainStack,
            );
        }

        if ($destinationStack === null) {
            return Result::failure(
                InventoryError::DestinationSlotDoesNotContainStack,
            );
        }

        if (
            ! $sourceStack->itemDefinitionId()->equals(
                $destinationStack->itemDefinitionId(),
            ) ||
            ! $sourceStack->compatibilityKey()->equals(
                $destinationStack->compatibilityKey(),
            )
        ) {
            return Result::failure(InventoryError::IncompatibleStacks);
        }

        if (
            ! $sourceStack->itemDefinitionId()->equals(
                $stackingProfile->itemDefinitionId(),
            ) ||
            ! $sourceStack->compatibilityKey()->equals(
                $stackingProfile->compatibilityKey(),
            )
        ) {
            return Result::failure(InventoryError::IncompatibleStacks);
        }

        if (! $stackingProfile->isStackable()) {
            return Result::failure(InventoryError::ItemNotStackable);
        }

        $destinationCapacity = $stackingProfile->maxStackSize()
            - $destinationStack->quantity()->value();

        if ($destinationCapacity <= 0) {
            return Result::failure(InventoryError::DestinationStackFull);
        }

        $quantityToMove = min(
            $sourceStack->quantity()->value(),
            $destinationCapacity,
        );

        $remainingSourceQuantity = $sourceStack->quantity()->value()
            - $quantityToMove;

        $updatedDestination = new ItemStack(
            $destinationStack->itemDefinitionId(),
            new ItemQuantity(
                $destinationStack->quantity()->value() + $quantityToMove,
            ),
            $destinationStack->compatibilityKey(),
        );

        $updatedSource = $remainingSourceQuantity > 0
            ? new ItemStack(
                $sourceStack->itemDefinitionId(),
                new ItemQuantity($remainingSourceQuantity),
                $sourceStack->compatibilityKey(),
            )
            : null;

        $this->slots[$destinationIndex] = new InventorySlot(
            $destinationIndex,
            $updatedDestination,
        );

        $this->slots[$sourceIndex] = new InventorySlot(
            $sourceIndex,
            $updatedSource,
        );

        $this->version++;

        $this->domainEvents[] = new ItemStacksMerged(
            inventoryId: $this->id,
            sourceSlotIndex: $sourceIndex,
            destinationSlotIndex: $destinationIndex,
            quantityMerged: $quantityToMove,
        );

        return Result::success([
            $sourceIndex,
            $destinationIndex,
        ]);
    }

    public function splitStack(
        int $sourceIndex,
        int $destinationIndex,
        ItemQuantity $quantity,
    ): Result {
        if (
            $sourceIndex < 0 ||
            $sourceIndex >= $this->capacity() ||
            $destinationIndex < 0 ||
            $destinationIndex >= $this->capacity()
        ) {
            return Result::failure(InventoryError::InvalidSlotIndex);
        }

        if ($sourceIndex === $destinationIndex) {
            return Result::failure(InventoryError::SameSlot);
        }

        $sourceSlot = $this->slots[$sourceIndex];
        $destinationSlot = $this->slots[$destinationIndex];

        $sourceStack = $sourceSlot->stack();

        if ($sourceStack === null) {
            return Result::failure(
                InventoryError::SourceSlotDoesNotContainStack,
            );
        }

        if (! $destinationSlot->isEmpty()) {
            return Result::failure(InventoryError::DestinationSlotNotEmpty);
        }

        $sourceQuantity = $sourceStack->quantity()->value();
        $splitQuantity = $quantity->value();

        if ($splitQuantity >= $sourceQuantity) {
            return Result::failure(InventoryError::InvalidSplitQuantity);
        }

        $remainingSourceQuantity = $sourceQuantity - $splitQuantity;

        $updatedSource = new ItemStack(
            $sourceStack->itemDefinitionId(),
            new ItemQuantity($remainingSourceQuantity),
            $sourceStack->compatibilityKey(),
        );

        $splitStack = new ItemStack(
            $sourceStack->itemDefinitionId(),
            $quantity,
            $sourceStack->compatibilityKey(),
        );

        $this->slots[$sourceIndex] = new InventorySlot(
            $sourceIndex,
            $updatedSource,
        );

        $this->slots[$destinationIndex] = new InventorySlot(
            $destinationIndex,
            $splitStack,
        );

        $this->version++;

        $this->domainEvents[] = new ItemStackSplit(
            inventoryId: $this->id,
            sourceSlotIndex: $sourceIndex,
            destinationSlotIndex: $destinationIndex,
            quantitySplit: $quantity->value(),
        );

        return Result::success([
            $sourceIndex,
            $destinationIndex,
        ]);
    }

    /**
     * @return list<DomainEvent>
     */
    public function domainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;

        $this->domainEvents = [];

        return $events;
    }

    /**
     * Reconstitutes an inventory from persisted state.
     *
     * This method restores the aggregate without recording domain events
     * or incrementing its version.
     *
     * @param  array<int, InventorySlot>  $slots
     */
    public static function reconstitute(
        InventoryId $id,
        CharacterId $characterId,
        int $version,
        array $slots,
    ): self {
        if ($version < 0) {
            throw new InvalidArgumentException(
                'Inventory version cannot be negative.',
            );
        }

        if ($slots === []) {
            throw new InvalidArgumentException(
                'An inventory must contain at least one slot.',
            );
        }

        if (! array_is_list($slots)) {
            throw new InvalidArgumentException(
                'Inventory slots must be provided as a zero-based list.',
            );
        }

        foreach ($slots as $index => $slot) {
            if ($slot->index() !== $index) {
                throw new InvalidArgumentException(
                    'Inventory slots must have contiguous zero-based indexes.',
                );
            }
        }

        $inventory = new self(
            $id,
            $characterId,
            count($slots),
        );

        foreach ($slots as $slot) {
            $inventory->slots[$slot->index()] = $slot;
        }

        $inventory->version = $version;

        return $inventory;
    }
}
