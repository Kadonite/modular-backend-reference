<?php

use Illuminate\Support\Facades\DB;

it('connects to the PostgreSQL integration database', function () {
    $connection = DB::connection();

    expect($connection->getDriverName())->toBe('pgsql')
        ->and($connection->getDatabaseName())->toBe('modular_backend_test');
});

it('can execute a PostgreSQL query', function () {
    $result = DB::select('SELECT version() AS version');

    expect($result)->toHaveCount(1)
        ->and($result[0]->version)->toContain('PostgreSQL');
});
