<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class InternationalWireRoutingInfo
{
    public function __construct(
        public string $iban,
        public string $swiftCode,
        public ?CorrespondentInfo $correspondentInfo,
        public ?BankDetails $bankDetails,
        public ?Address $address,
        public ?string $phoneNumber,
        public ?CountrySpecific $countrySpecific
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['iban'],
            $data['swiftCode'],
            ($data['correspondentInfo'] ?? null) ? CorrespondentInfo::fromArray($data['correspondentInfo']) : null,
            ($data['bankDetails'] ?? null) ? BankDetails::fromArray($data['bankDetails']) : null,
            ($data['address'] ?? null) ? Address::fromArray($data['address']) : null,
            $data['phoneNumber'],
            ($data['countrySpecific'] ?? null) ? CountrySpecific::fromArray($data['countrySpecific']) : null,
        );
    }
}