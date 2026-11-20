<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum InventoryError: string
{
    case InventoryFull = 'inventory_full';

    case ItemInstanceAlreadyPresent = 'item_instance_already_present';

    case InsufficientCapacity = 'insufficient_capacity';

    case ItemNotStackable = 'item_not_stackable';

    case InsufficientQuantity = 'insufficient_quantity';

    case ItemInstanceNotFound = 'item_instance_not_found';

    case InvalidSlotIndex = 'invalid_slot_index';

    case SourceSlotEmpty = 'source_slot_empty';

    case DestinationSlotOccupied = 'destination_slot_occupied';

    case SameSlot = 'same_slot';

    case BothSlotsEmpty = 'both_slots_empty';

    case IncompatibleStacks = 'incompatible_stacks';

    case DestinationStackFull = 'destination_stack_full';

    case SourceSlotDoesNotContainStack = 'source_slot_does_not_contain_stack';

    case DestinationSlotDoesNotContainStack = 'destination_slot_does_not_contain_stack';

    case DestinationSlotNotEmpty = 'destination_slot_not_empty';

    case InvalidSplitQuantity = 'invalid_split_quantity';
}
