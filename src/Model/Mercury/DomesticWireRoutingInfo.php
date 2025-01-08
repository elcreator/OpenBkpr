<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class DomesticWireRoutingInfo
{
    public function __construct(
        public ?string $bankName,
        public string $accountNumber,
        public string $routingNumber,
        public ?Address $address
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['bankName'],
            $data['accountNumber'],
            $data['routingNumber'],
            ($data['address'] ?? null) ? Address::fromArray($data['address']) : null,
        );
    }
}
