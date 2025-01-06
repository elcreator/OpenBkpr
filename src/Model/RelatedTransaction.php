<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

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