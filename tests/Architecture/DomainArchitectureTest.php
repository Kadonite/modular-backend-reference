
<?php

arch('domain classes use strict types')
    ->expect('App\Modules\Inventory\Domain')
    ->toUseStrictTypes();

arch('domain classes do not depend on Laravel')
    ->expect('App\Modules\Inventory\Domain')
    ->not->toUse('Illuminate');

arch('domain classes do not depend on infrastructure')
    ->expect('App\Modules\Inventory\Domain')
    ->not->toUse('App\Modules\Inventory\Infrastructure');

arch('application code avoids debugging functions')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'die', 'var_dump']);
