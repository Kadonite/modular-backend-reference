<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE inventories
            ADD CONSTRAINT inventories_positive_capacity
            CHECK (capacity > 0)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE inventories
            ADD CONSTRAINT inventories_non_negative_version
            CHECK (version >= 0)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE inventory_slots
            DROP CONSTRAINT inventory_slots_valid_content
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

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE inventory_slots DROP CONSTRAINT inventory_slots_valid_content'
        );

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

        DB::statement(
            'ALTER TABLE inventories DROP CONSTRAINT inventories_non_negative_version'
        );

        DB::statement(
            'ALTER TABLE inventories DROP CONSTRAINT inventories_positive_capacity'
        );
    }
};
