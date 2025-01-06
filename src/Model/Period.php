<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model;

readonly class Period
{
    public function __construct(
        public \DateTimeImmutable $fromDate,
        public \DateTimeImmutable $toDate,
    ) {}
}