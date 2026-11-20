<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Enums;

enum InventoryRepositoryError: string
{
    case ConcurrencyConflict = 'concurrency_conflict';
}
