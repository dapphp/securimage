<?php

require_once __DIR__ . '/../securimage.php';

$options = array(
    'use_database'    => true,
    'database_driver' => Securimage::SI_DRIVER_PGSQL,
    'database_host'   => 'localhost',
    'database_user'   => 'securimage',
    'database_pass'   => 'password1234',
    'database_name'   => 'test',
    'no_exit'         => true,
);

$securimage = new Securimage($options);

$securimage->show();
