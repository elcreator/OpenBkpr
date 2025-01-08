<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class ElectronicRoutingInfo
{
    public function __construct(
        public string $accountNumber,
        public string $routingNumber,
        public ?string $bankName
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['accountNumber'],
            $data['routingNumber'],
            ($data['bankName'] ?? null),
        );
    }
}