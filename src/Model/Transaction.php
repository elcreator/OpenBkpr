<?php

namespace App\Model;

readonly class Transaction
{
    public function __construct(
        public string  $id,
        public float   $amount,
        public \DateTimeImmutable  $postedAt,
        public \DateTimeImmutable  $createdAt,
        public string  $counterpartyName,
        public ?string $note,
    ){}
}