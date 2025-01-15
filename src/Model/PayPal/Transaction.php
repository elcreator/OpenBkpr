<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\PayPal;

readonly class Transaction
{
    public function __construct(
        public string $transactionId,
        public string $transactionEventCode,
        public \DateTimeImmutable $transactionInitiationDate,
        public \DateTimeImmutable $transactionUpdatedDate,
        public Money $transactionAmount,
        public ?Money $feeAmount,
        public PayPalTransactionStatus $transactionStatus,
        public ?string $paypalAccountId,
        public ?string $paypalReferenceId,
        public ?string $paypalReferenceIdType,
        public ?string $transactionSubject,
        public ?string $transactionNote,
        public Money $endingBalance,
        public Money $availableBalance,
        public ?string $invoiceId,
        public ?string $customField,
        public ?string $protectionEligibility,
        public ?string $instrumentType,
        public ?string $instrumentSubType,
    ) {
    }

    /**
     * Creates a PayPalTransaction instance from an array of data
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $transactionInfo = $data['transaction_info'];

        return new self(
            transactionId: $transactionInfo['transaction_id'],
            transactionEventCode: $transactionInfo['transaction_event_code'],
            transactionInitiationDate: new \DateTimeImmutable($transactionInfo['transaction_initiation_date']),
            transactionUpdatedDate: new \DateTimeImmutable($transactionInfo['transaction_updated_date']),
            transactionAmount: Money::fromArray($transactionInfo['transaction_amount']),
            feeAmount: isset($transactionInfo['fee_amount'])
                ? Money::fromArray($transactionInfo['fee_amount'])
                : null,
            transactionStatus: PayPalTransactionStatus::from($transactionInfo['transaction_status']),
            paypalAccountId: $transactionInfo['paypal_account_id'] ?? null,
            paypalReferenceId: $transactionInfo['paypal_reference_id'] ?? null,
            paypalReferenceIdType: $transactionInfo['paypal_reference_id_type'] ?? null,
            transactionSubject: $transactionInfo['transaction_subject'] ?? null,
            transactionNote: $transactionInfo['transaction_note'] ?? null,
            endingBalance: Money::fromArray($transactionInfo['ending_balance']),
            availableBalance: Money::fromArray($transactionInfo['available_balance']),
            invoiceId: $transactionInfo['invoice_id'] ?? null,
            customField: $transactionInfo['custom_field'] ?? null,
            protectionEligibility: $transactionInfo['protection_eligibility'] ?? null,
            instrumentType: $transactionInfo['instrument_type'] ?? null,
            instrumentSubType: $transactionInfo['instrument_sub_type'] ?? null,
        );
    }

    public function toTransaction(): \App\Model\Transaction
    {
        return new \App\Model\Transaction(
            $this->transactionId,
            $this->transactionAmount->value,
            $this->transactionInitiationDate,
            $this->transactionUpdatedDate,
            $this->paypalAccountId ?? 'PayPal',
            $this->transactionNote ?? $this->transactionSubject ?? ''
        );
    }
}