<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury\CountrySpecific;

readonly class India
{
    public function __construct(
        public string $ifscCode
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['ifscCode'],
        );
    }
}