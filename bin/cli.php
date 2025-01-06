<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

define('BASE_DIR', realpath(__DIR__ . '/..'));
require_once BASE_DIR . '/vendor/autoload.php';

$app = new \App\Cli();
$app->execute();
