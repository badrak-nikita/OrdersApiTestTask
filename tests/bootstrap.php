<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'loadEnv')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env.test');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
