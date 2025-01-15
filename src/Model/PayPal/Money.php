<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\PayPal;

readonly class Money
{
    public function __construct(
        public string $currencyCode,
        public float $value,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            currencyCode: $data['currency_code'],
            value: (float) $data['value'],
        );
    }
}