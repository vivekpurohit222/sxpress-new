<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = [
    'users',
    'roles',
    'permissions',
    'branches',
    'customers',
    'vendors',
    'trucks',
    'drivers',
    'grs',
    'gatepasses',
    'challans',
    'challan_iteams',
    'frieghts'
];

foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        echo "$table: $count\n";
    } catch (\Exception $e) {
        echo "$table: ERROR (" . $e->getMessage() . ")\n";
    }
}
