<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */
require_once realpath(__DIR__ . '/..') . '/vendor/autoload.php';
(new \App\Cli())->run($argv ?? []);
