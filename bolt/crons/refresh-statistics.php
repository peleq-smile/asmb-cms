#!/usr/bin/env php
<?php
/** @var \Silex\Application $app */

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;

$app = require __DIR__ . '/../vendor/bolt/bolt/app/bootstrap.php';
$app->boot();

/** @var Application $nut Nut Console Application */
$nut = $app['nut'];

try {
    $nut->run(new StringInput('asmb:visitor-statistics:refresh'));
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
