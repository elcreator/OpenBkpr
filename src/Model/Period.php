<?php

namespace App\Model;

readonly class Period
{
    public function __construct(
        public \DateTimeImmutable $fromDate,
        public \DateTimeImmutable $toDate,
    ) {}
}