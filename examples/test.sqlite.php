<?php

require_once __DIR__ . '/../securimage.php';

$t0      = microtime(true);
$options = array(
    'no_exit'         => true,
    'use_database'    => true,
    'database_driver' => Securimage::SI_DRIVER_SQLITE3,
);

$securimage = new Securimage($options);

$securimage->show();

$t1 = microtime(true);
