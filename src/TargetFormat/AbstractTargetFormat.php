<?php

namespace App\TargetFormat;

use App\Model;

abstract class AbstractTargetFormat
{
    abstract public function getExtension(): string;

    /**
     * @param Model\Transaction[] $transactions
     * @param Model\AccountInfo $accountInfo
     * @param Model\Period $period
     * @return false|string
     */
    abstract public function generateFromTransactions(array $transactions, Model\AccountInfo $accountInfo,
        Model\Period $period): false|string;
}