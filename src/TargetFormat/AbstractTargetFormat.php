<?php

namespace App\TargetFormat;

abstract class AbstractTargetFormat
{
    public function generateFromTransactions($transactions, $accountInfo, $period) {}
}