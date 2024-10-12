<?php

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../vendor/autoload.php';

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => getenv('MYSQLHOST'),
    'port'      => getenv('MYSQLPORT'),
    'database'  => getenv('MYSQLDATABASE'),
    'username'  => getenv('MYSQLUSER'),
    'password'  => getenv('MYSQLPASSWORD'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
