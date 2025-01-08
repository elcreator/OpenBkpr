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
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['routingNumber'],
        );
    }
}