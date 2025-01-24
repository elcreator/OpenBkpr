<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury\CountrySpecific;

readonly class Philippines
{
    public function __construct(
        public string $routingNumber
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['routingNumber'],
        );
    }
}