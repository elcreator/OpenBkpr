<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\TargetFormat;

use App\Model;

class Json extends AbstractTargetFormat {
    private $extension = 'json';

    public function __construct() {
    }

    /**
     * @param Model\Transaction[] $transactions
     * @param Model\AccountInfo $accountInfo
     * @param Model\Period $period
     * @return false|string
     */
    public function generateFromTransactions($transactions, $accountInfo, $period) {
        return json_encode(['transactions' => $transactions, 'accountInfo' => $accountInfo, 'period' => $period]);
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
