<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class CorrespondentInfo
{
    public function __construct(
        public ?string $routingNumber,
        public ?string $swiftCode,
        public ?string $bankName
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ($data['routingNumber'] ?? null),
            ($data['swiftCode'] ?? null),
            ($data['bankName'] ?? null)
        );
    }
}