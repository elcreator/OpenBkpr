<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury\CountrySpecific;

readonly class Canada
{
    public function __construct(
        public string $bankCode,
        public string $transitNumber
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['bankCode'],
            $data['transitNumber'],
        );
    }
}