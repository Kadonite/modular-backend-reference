<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Mappers;

use App\Modules\Character\Domain\ValueObjects\CharacterId;
use App\Modules\Inventory\Domain\Aggregates\Inventory;
use App\Modules\Inventory\Domain\Entities\InventorySlot;
use App\Modules\Inventory\Domain\ValueObjects\InventoryId;
use App\Modules\Inventory\Domain\ValueObjects\ItemQuantity;
use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryRecord;
use App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySlotRecord;
use App\Modules\Item\Domain\ValueObjects\ItemDefinitionId;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use App\Modules\Item\Domain\ValueObjects\StackCompatibilityKey;
use UnexpectedValueException;

final class InventoryMapper
{
    public function toDomain(InventoryRecord $record): Inventory
    {
        $slotRecords = $record->slots()
            ->orderBy('slot_index')
            ->get();

        $slots = [];

        foreach ($slotRecords as $slotRecord) {
            $slots[] = $this->slotToDomain($slotRecord);
        }

        return Inventory::reconstitute(
            id: new InventoryId($record->getKey()),
            characterId: new CharacterId($record->character_id),
            version: $record->version,
            slots: $slots,
        );
    }

    private function slotToDomain(
        InventorySlotRecord $record,
    ): InventorySlot {
        $itemDefinitionId = $record->item_definition_id;
        $quantity = $record->quantity;
        $compatibilityKey = $record->compatibility_key;
        $itemInstanceId = $record->item_instance_id;

        $hasDefinition = $itemDefinitionId !== null;
        $hasQuantity = $quantity !== null;
        $hasCompatibilityKey = $compatibilityKey !== null;
        $hasItemInstance = $itemInstanceId !== null;

        $hasStackData = $hasDefinition
            || $hasQuantity
            || $hasCompatibilityKey;

        if ($hasItemInstance && $hasStackData) {
            throw new UnexpectedValueException(
                'An inventory slot cannot contain both a stack and a unique item instance.',
            );
        }

        if ($hasItemInstance) {
            return new InventorySlot(
                index: $record->slot_index,
                content: new ItemInstanceId($itemInstanceId),
            );
        }

        if (! $hasStackData) {
            return new InventorySlot(
                index: $record->slot_index,
                content: null,
            );
        }

        if (
            ! $hasDefinition
            || ! $hasQuantity
            || ! $hasCompatibilityKey
        ) {
            throw new UnexpectedValueException(
                'An inventory stack must contain a definition ID, quantity and compatibility key.',
            );
        }

        return new InventorySlot(
            index: $record->slot_index,
            content: new ItemStack(
                itemDefinitionId: new ItemDefinitionId($itemDefinitionId),
                quantity: new ItemQuantity($quantity),
                compatibilityKey: new StackCompatibilityKey($compatibilityKey),
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     character_id: string,
     *     capacity: int,
     *     version: int
     * }
     */
    public function toRecordAttributes(Inventory $inventory): array
    {
        return [
            'id' => $inventory->id()->value(),
            'character_id' => $inventory->characterId()->value(),
            'capacity' => $inventory->capacity(),
            'version' => $inventory->version(),
        ];
    }

    /**
     * @return list<array{
     *     inventory_id: string,
     *     slot_index: int,
     *     item_definition_id: string|null,
     *     quantity: int|null,
     *     compatibility_key: string|null,
     *     item_instance_id: string|null
     * }>
     */
    public function toSlotRecordAttributes(Inventory $inventory): array
    {
        $records = [];

        foreach ($inventory->slots() as $slot) {
            $record = [
                'inventory_id' => $inventory->id()->value(),
                'slot_index' => $slot->index(),
                'item_definition_id' => null,
                'quantity' => null,
                'compatibility_key' => null,
                'item_instance_id' => null,
            ];

            $content = $slot->content();

            if ($content instanceof ItemStack) {
                $record['item_definition_id'] = $content->itemDefinitionId()->value();
                $record['quantity'] = $content->quantity()->value();
                $record['compatibility_key'] = $content->compatibilityKey()->value();
            } elseif ($content instanceof ItemInstanceId) {
                $record['item_instance_id'] = $content->value();
            }

            $records[] = $record;
        }

        return $records;
    }
}
