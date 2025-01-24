<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class TransactionDetails
{
    public function __construct(
        public ?Address $address,
        public ?DomesticWireRoutingInfo $domesticWireRoutingInfo,
        public ?ElectronicRoutingInfo $electronicRoutingInfo,
        public ?InternationalWireRoutingInfo $internationalWireRoutingInfo,
        public ?CardInfo $debitCardInfo,
        public ?CardInfo $creditCardInfo
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ($data['address'] ?? null) ? Address::fromArray($data['address']) : null,
            ($data['domesticWireRoutingInfo'] ?? null) ? DomesticWireRoutingInfo::fromArray(
                $data['domesticWireRoutingInfo']
            ) : null,
            ($data['electronicRoutingInfo'] ?? null) ? ElectronicRoutingInfo::fromArray(
                $data['electronicRoutingInfo']
            ) : null,
            ($data['internationalWireRoutingInfo'] ?? null) ? InternationalWireRoutingInfo::fromArray(
                $data['internationalWireRoutingInfo']
            ) : null,
            ($data['debitCardInfo'] ?? null) ? CardInfo::fromArray($data['debitCardInfo']) : null,
            ($data['creditCardInfo'] ?? null) ? CardInfo::fromArray($data['creditCardInfo']) : null,
        );
    }
}