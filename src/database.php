<?php

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../vendor/autoload.php';

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => getenv('MYSQL_HOST'),
    'port'      => getenv('MYSQL_PORT'),
    'database'  => getenv('MYSQL_DATABASE'),
    'username'  => getenv('MYSQL_USER'),
    'password'  => getenv('MYSQL_PASSWORD'),
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);


$capsule->setAsGlobal();
$capsule->bootEloquent();
