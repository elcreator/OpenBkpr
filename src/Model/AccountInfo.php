<?php

namespace App\Model;

readonly class AccountInfo
{
    public function __construct(
        public string $accountId,
        public string $accountNumber,
        public string $ownerName,
        public string $currency,
    ) {}
}