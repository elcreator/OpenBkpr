<?php

namespace App\TargetFormat;

abstract class AbstractTargetFormat
{
    abstract public function getExtension(): string;
    abstract public function generateFromTransactions($transactions, $accountInfo, $period);
}