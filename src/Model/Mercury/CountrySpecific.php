<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class CountrySpecific
{
    public function __construct(
        public ?CountrySpecific\Canada $countrySpecificDataCanada,
        public ?CountrySpecific\Australia $countrySpecificDataAustralia,
        public ?CountrySpecific\India $countrySpecificDataIndia,
        public ?CountrySpecific\Philippines $countrySpecificDataPhilippines,
        public ?CountrySpecific\Russia $countrySpecificDataRussia,
        public ?CountrySpecific\SouthAfrica $countrySpecificDataSouthAfrica
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ($data['countrySpecificDataCanada'] ?? null) ?
                CountrySpecific\Canada::fromArray($data['countrySpecificDataCanada']) : null,
            ($data['countrySpecificDataAustralia'] ?? null) ?
                CountrySpecific\Australia::fromArray($data['countrySpecificDataAustralia']) : null,
            ($data['countrySpecificDataIndia'] ?? null) ?
                CountrySpecific\India::fromArray($data['countrySpecificDataIndia']) : null,
            ($data['countrySpecificDataPhilippines'] ?? null) ?
                CountrySpecific\Philippines::fromArray($data['countrySpecificDataPhilippines']) : null,
            ($data['countrySpecificDataRussia'] ?? null) ?
                CountrySpecific\Russia::fromArray($data['countrySpecificDataRussia']) : null,
            ($data['countrySpecificDataSouthAfrica'] ?? null) ?
                CountrySpecific\SouthAfrica::fromArray($data['countrySpecificDataSouthAfrica']) : null,
        );
    }
}