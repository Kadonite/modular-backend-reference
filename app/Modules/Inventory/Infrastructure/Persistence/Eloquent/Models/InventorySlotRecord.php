<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventorySlotRecord extends Model
{
    protected $table = 'inventory_slots';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'inventory_id',
        'slot_index',
        'item_definition_id',
        'quantity',
        'compatibility_key',
        'item_instance_id',
    ];

    protected function casts(): array
    {
        return [
            'slot_index' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InventoryRecord, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(
            InventoryRecord::class,
            'inventory_id',
            'id',
        );
    }
}
