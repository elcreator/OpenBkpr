<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury\CountrySpecific;

readonly class SouthAfrica
{
    public function __construct(
        public string $branchCode
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['branchCode'],
        );
    }
}