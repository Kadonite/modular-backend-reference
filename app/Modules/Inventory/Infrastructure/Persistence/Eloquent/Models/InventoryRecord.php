<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryRecord extends Model
{
    protected $table = 'inventories';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'character_id',
        'capacity',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return HasMany<InventorySlotRecord, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(
            InventorySlotRecord::class,
            'inventory_id',
            'id',
        );
    }
}
