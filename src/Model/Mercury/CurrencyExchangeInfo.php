<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class CurrencyExchangeInfo
{
    public function __construct(
        public string $convertedFromCurrency,
        public string $convertedToCurrency,
        public float $convertedFromAmount,
        public float $convertedToAmount,
        public float $feeAmount,
        public float $feePercentage,
        public float $exchangeRate,
        public string $feeTransactionId
    ) {}
    public static function fromArray(array $data): self
    {
        return new self(
            $data['convertedFromCurrency'],
            $data['convertedToCurrency'],
            $data['convertedFromAmount'],
            $data['convertedToAmount'],
            $data['feeAmount'],
            $data['feePercentage'],
            $data['exchangeRate'],
            $data['feeTransactionId']
        );
    }
}