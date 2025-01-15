<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Stripe;

readonly class FeeDetails
{
    public function __construct(
        public int $amount,
        public ?string $application,
        public string $currency,
        public ?string $description,
        public FeeDetailsType $type
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: $data['amount'],
            application: $data['application'] ?? null,
            currency: $data['currency'],
            description: $data['description'] ?? null,
            type: FeeDetailsType::from($data['type'])
        );
    }
}