<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class Transaction
{
    public function __construct(
        public float               $amount,
        public ?string             $bankDescription,
        public string              $counterpartyId,
        public string              $counterpartyName,
        public ?string             $counterpartyNickname,
        public \DateTimeImmutable  $createdAt,
        public string              $dashboardLink,
        public ?TransactionDetails $details,
        public string              $estimatedDeliveryDate,
        public ?\DateTimeImmutable $failedAt,
        public string              $id,
        public TransactionKind     $kind,
        public ?string             $note,
        public ?string             $externalMemo,
        public \DateTimeImmutable  $postedAt,
        public ?string             $reasonForFailure,
        public TransactionStatus   $status,
        public ?string             $feeId,
        public ?object  $currencyExchangeInfo,
        public bool     $compliantWithReceiptPolicy,
        public bool     $hasGeneratedReceipt,
        public ?string  $creditAccountPeriodId,
        public ?string  $mercuryCategory,
        public ?string  $generalLedgerCodeName,
        /** @var Attachment[] */
        public array    $attachments,
        /** @var RelatedTransaction[] */
        public array    $relatedTransactions,
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
        $attachments = array_map(
            fn(array $attachment) => new Attachment(
                fileName: $attachment['fileName'],
                url: $attachment['url'],
                attachmentType: AttachmentType::from($attachment['attachmentType']),
            ),
            $data['attachments'] ?? []
        );

        return new self(
            amount: $data['amount'],
            bankDescription: $data['bankDescription'],
            counterpartyId: $data['counterpartyId'],
            counterpartyName: $data['counterpartyName'],
            counterpartyNickname: $data['counterpartyNickname'],
            createdAt: new \DateTimeImmutable($data['createdAt']),
            dashboardLink: $data['dashboardLink'],
            details: ($data['details'] ?? null) ? TransactionDetails::fromArray($data['details']) : null,
            estimatedDeliveryDate: $data['estimatedDeliveryDate'],
            failedAt: ($data['failedAt'] ?? null) ? new \DateTimeImmutable($data['failedAt']) : null,
            id: $data['id'],
            kind: TransactionKind::tryFrom($data['kind']) ?? TransactionKind::OTHER,
            note: $data['note'],
            externalMemo: $data['externalMemo'],
            postedAt: new \DateTimeImmutable($data['postedAt']),
            reasonForFailure: $data['reasonForFailure'],
            status: TransactionStatus::from($data['status']),
            feeId: $data['feeId'],
            currencyExchangeInfo: $data['currencyExchangeInfo'] ? CurrencyExchangeInfo::fromArray(
                $data['currencyExchangeInfo']) : null,
            compliantWithReceiptPolicy: $data['compliantWithReceiptPolicy'],
            hasGeneratedReceipt: $data['hasGeneratedReceipt'],
            creditAccountPeriodId: $data['creditAccountPeriodId'],
            mercuryCategory: $data['mercuryCategory'],
            generalLedgerCodeName: $data['generalLedgerCodeName'],
            attachments: $attachments,
            relatedTransactions: $relatedTransactions,
        );
    }

    public function toTransaction(): \App\Model\Transaction
    {
        return new \App\Model\Transaction($this->id, $this->amount, $this->postedAt, $this->createdAt, $this->counterpartyName, $this->note);
    }
}