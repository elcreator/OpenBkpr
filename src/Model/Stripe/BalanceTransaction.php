<?php

namespace App\Model\Stripe;

use App\Model\Transaction;

readonly class BalanceTransaction
{
    /**
     * @param FeeDetails[] $feeDetails
     */
    public function __construct(
        public string $id,
        public int $amount,
        public \DateTimeImmutable $availableOn,
        public \DateTimeImmutable $created,
        public string $currency,
        public ?string $description,
        public int $fee,
        public array $feeDetails,
        public int $net,
        public ?string $source,
        public string $status,
        public TransactionType $type
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            amount: $data['amount'],
            availableOn: new \DateTimeImmutable('@' . $data['available_on']),
            created: new \DateTimeImmutable('@' . $data['created']),
            currency: $data['currency'],
            description: $data['description'] ?? null,
            fee: $data['fee'],
            feeDetails: array_map(fn(array $feeDetails) => FeeDetails::fromArray($feeDetails), $data['fee_details']),
            net: $data['net'],
            source: $data['source'] ?? null,
            status: $data['status'],
            type: TransactionType::from($data['type'])
        );
    }

    public function toTransaction(): Transaction
    {
        return new \App\Model\Transaction(
            $this->id, $this->amount / 100, $this->availableOn, $this->created,
            $this->source, $this->description
        );
    }
}