<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model;

readonly class AccountInfo
{
    public function __construct(
        public string $accountId,
        public string $accountNumber,
        public string $ownerName,
        public string $currency,
    ) {
    }
}