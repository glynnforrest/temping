<?php

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Composer autoloader not found.'.PHP_EOL.
        'Please run `composer install`.'.PHP_EOL);
}

$loader->add('Temping\Test', __DIR__);
