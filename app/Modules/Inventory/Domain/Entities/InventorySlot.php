<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Entities;

use App\Modules\Inventory\Domain\ValueObjects\ItemStack;
use App\Modules\Item\Domain\ValueObjects\ItemInstanceId;
use InvalidArgumentException;

final readonly class InventorySlot
{
    public function __construct(
        private int $index,
        private ItemStack|ItemInstanceId|null $content = null
    ) {
        if ($this->index < 0) {
            throw new InvalidArgumentException(
                'Inventory slot index cannot be negative.'
            );
        }
    }

    public function index(): int
    {
        return $this->index;
    }

    public function content(): ItemStack|ItemInstanceId|null
    {
        return $this->content;
    }

    public function isEmpty(): bool
    {
        return $this->content === null;
    }

    public function containsStack(): bool
    {
        return $this->content instanceof ItemStack;
    }

    public function containsItemInstance(): bool
    {
        return $this->content instanceof ItemInstanceId;
    }

    public function stack(): ?ItemStack
    {
        return $this->content instanceof ItemStack
            ? $this->content
            : null;
    }

    public function itemInstanceId(): ?ItemInstanceId
    {
        return $this->content instanceof ItemInstanceId
            ? $this->content
            : null;
    }
}
