<?php
declare(strict_types=1);

require __DIR__."/../vendor/autoload.php";

use Lepidosteus\Geoip\Config;
use Lepidosteus\Geoip\Maxmind;

$config = new Config(
    __DIR__.'/database.mmdb',
    'YourLicenceKey',
);
$geoip = new Maxmind($config);

$geoip->updateDatabase();

$ip = '8.8.8.8';

var_dump($geoip->country($ip));
var_dump($geoip->registered_country($ip));
var_dump($geoip->continent($ip));
