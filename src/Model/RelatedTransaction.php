<?php

namespace App\Model;

readonly class RelatedTransaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId,
        public readonly string $relationKind,
        public readonly float $amount,
    ) {
    }
}