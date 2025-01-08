<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class RelatedTransaction
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $relationKind,
        public float  $amount,
    ) {
    }
}