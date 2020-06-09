<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 23/01/2019
 * Time: 23:00
 */

session_start();

error_reporting(E_NOTICE);

date_default_timezone_set("Europe/Paris");

require_once __DIR__.'/../vendor/autoload.php';

$app = new \myCLAP\Application(__DIR__.'/../');
$app->run();