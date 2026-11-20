<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_slots', function (Blueprint $table): void {
            $table->text('inventory_id');
            $table->integer('slot_index');

            $table->text('item_definition_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->text('compatibility_key')->nullable();
            $table->text('item_instance_id')->nullable();

            $table->timestamps();

            $table->primary(['inventory_id', 'slot_index']);

            $table->foreign('inventory_id')
                ->references('id')
                ->on('inventories')
                ->cascadeOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE inventory_slots
                ADD CONSTRAINT inventory_slots_non_negative_index
                CHECK (slot_index >= 0)
            SQL);

            DB::statement(<<<'SQL'
                ALTER TABLE inventory_slots
                ADD CONSTRAINT inventory_slots_valid_content
                CHECK (
                    (
                        item_definition_id IS NULL
                        AND quantity IS NULL
                        AND compatibility_key IS NULL
                        AND item_instance_id IS NULL
                    )
                    OR
                    (
                        item_definition_id IS NOT NULL
                        AND quantity IS NOT NULL
                        AND quantity > 0
                        AND compatibility_key IS NOT NULL
                        AND item_instance_id IS NULL
                    )
                    OR
                    (
                        item_definition_id IS NULL
                        AND quantity IS NULL
                        AND compatibility_key IS NULL
                        AND item_instance_id IS NOT NULL
                    )
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_slots');
    }
};
