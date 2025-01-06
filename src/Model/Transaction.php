<?php

namespace App\Model;

readonly class Transaction
{
    /**
     * @param RelatedTransaction[] $relatedTransactions
     * @param array<string, mixed> $attachments
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly string  $id,
        public readonly ?string $feeId,
        public readonly float   $amount,
        public readonly string  $createdAt,
        public readonly string  $postedAt,
        public readonly string  $estimatedDeliveryDate,
        public readonly string  $status,
        public readonly ?string $note,
        public readonly ?string $bankDescription,
        public readonly ?string $externalMemo,
        public readonly string  $counterpartyId,
        public readonly array   $details,
        public readonly ?string $reasonForFailure,
        public readonly ?string $failedAt,
        public readonly string  $dashboardLink,
        public readonly string  $counterpartyName,
        public readonly ?string $counterpartyNickname,
        public readonly string  $kind,
        public readonly ?object $currencyExchangeInfo,
        public readonly bool    $compliantWithReceiptPolicy,
        public readonly bool    $hasGeneratedReceipt,
        public readonly ?string $creditAccountPeriodId,
        public readonly ?string  $mercuryCategory,
        public readonly ?string  $generalLedgerCodeName,
        public readonly array   $attachments,
        public readonly array   $relatedTransactions,
    )
    {
    }

    /**
     * Creates a Transaction instance from an array of data
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $relatedTransactions = array_map(
            fn(array $transaction) => new RelatedTransaction(
                id: $transaction['id'],
                accountId: $transaction['accountId'],
                relationKind: $transaction['relationKind'],
                amount: $transaction['amount'],
            ),
            $data['relatedTransactions'] ?? []
        );

        return new self(
            id: $data['id'],
            feeId: $data['feeId'],
            amount: $data['amount'],
            createdAt: $data['createdAt'],
            postedAt: $data['postedAt'],
            estimatedDeliveryDate: $data['estimatedDeliveryDate'],
            status: $data['status'],
            note: $data['note'],
            bankDescription: $data['bankDescription'],
            externalMemo: $data['externalMemo'],
            counterpartyId: $data['counterpartyId'],
            details: $data['details'] ?? [],
            reasonForFailure: $data['reasonForFailure'],
            failedAt: $data['failedAt'],
            dashboardLink: $data['dashboardLink'],
            counterpartyName: $data['counterpartyName'],
            counterpartyNickname: $data['counterpartyNickname'],
            kind: $data['kind'],
            currencyExchangeInfo: $data['currencyExchangeInfo'],
            compliantWithReceiptPolicy: $data['compliantWithReceiptPolicy'],
            hasGeneratedReceipt: $data['hasGeneratedReceipt'],
            creditAccountPeriodId: $data['creditAccountPeriodId'],
            mercuryCategory: $data['mercuryCategory'],
            generalLedgerCodeName: $data['generalLedgerCodeName'],
            attachments: $data['attachments'] ?? [],
            relatedTransactions: $relatedTransactions,
        );
    }
}