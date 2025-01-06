<?php
require_once '../vendor/autoload.php';

define('BASE_DIR', realpath(__DIR__ . '/..'));

$app = new \App\Cli();
$app->execute();
