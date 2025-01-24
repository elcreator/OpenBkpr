<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class BankDetails
{
    public function __construct(
        public string $bankName,
        public ?string $cityState,
        public ?string $country
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['bankName'],
            ($data['cityState'] ?? null),
            ($data['country'] ?? null)
        );
    }
}