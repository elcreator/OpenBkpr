<?php

namespace App\TargetFormat;

class Factory
{
    const ALL_FORMATS = [
        'CAMT54' => Camt054_1_04::class,
        'CSV' => Csv::class,
        'JSON' => Json::class,
    ];

    public function create(string $format): AbstractTargetFormat
    {
        if (!array_key_exists($format, self::ALL_FORMATS)) {
            throw new \InvalidArgumentException("Unsupported format: $format");
        }
        return new (self::ALL_FORMATS[$format])();
    }
}