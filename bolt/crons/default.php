#!/usr/bin/env php
<?php
/** @var \Silex\Application $app */
use Symfony\Component\Console\Input\StringInput;

$app = require __DIR__ . '/../vendor/bolt/bolt/app/bootstrap.php';
$app->boot();

/** @var \Symfony\Component\Console\Application $nut Nut Console Application */
$nut = $app['nut'];

try {
    $nut->run(new StringInput('cron'));
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
