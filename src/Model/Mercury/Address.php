<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class Address
{
    public function __construct(
        public string $address1,
        public ?string $address2,
        public string $city,
        public ?string $state,
        public string $postalCode,
        public ?string $region = null,
        public ?string $country = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['address1'],
            ($data['address2'] ?? null),
            $data['city'],
            ($data['state'] ?? null),
            $data['postalCode'],
            ($data['region'] ?? null),
            ($data['country'] ?? null),
        );
    }
}