<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\TargetFormat;

use App\Model;

class Camt054_1_04 extends AbstractTargetFormat
{
    const DT_FORMAT = 'Y-m-d\TH:i:s';
    private $extension = 'camt054.xml';
    private $xmlDoc;
    private $namespace = 'urn:iso:std:iso:20022:tech:xsd:camt.054.001.04';

    public function __construct()
    {
        $this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDoc->formatOutput = true;
    }

    /**
     * @param Model\Transaction[] $transactions
     * @param Model\AccountInfo $accountInfo
     * @param Model\Period $period
     * @return false|string
     * @throws \DOMException
     */
    public function generateFromTransactions($transactions, $accountInfo, $period)
    {
        $document = $this->xmlDoc->createElementNS($this->namespace, 'Document');
        $this->xmlDoc->appendChild($document);

        $notification = $this->xmlDoc->createElement('BkToCstmrDbtCdtNtfctn');
        $document->appendChild($notification);

        $this->addGroupHeader($notification);

        $this->addNotification($notification, $transactions, $accountInfo, $period);

        return $this->xmlDoc->saveXML();
    }

    private function addGroupHeader($parent)
    {
        $grpHdr = $this->xmlDoc->createElement('GrpHdr');
        $parent->appendChild($grpHdr);

        // Message ID - using timestamp and random number
        $grpHdr->appendChild($this->xmlDoc->createElement('MsgId', date('Ymd') . sprintf('%06d', rand(0, 999999))));

        // Creation Date Time
        $grpHdr->appendChild($this->createDateTimeNode('CreDtTm'));

        // Pagination
        $msgPgntn = $this->xmlDoc->createElement('MsgPgntn');
        $grpHdr->appendChild($msgPgntn);
        $msgPgntn->appendChild($this->xmlDoc->createElement('PgNb', '1'));
        $msgPgntn->appendChild($this->xmlDoc->createElement('LastPgInd', 'true'));
    }

    private function createDateTimeNode($name, $dateTime = null)
    {
        if (is_null($dateTime)) {
            $dateTime = new \DateTimeImmutable();
        }
        return $this->xmlDoc->createElement($name, $dateTime->format(self::DT_FORMAT));
    }

    /**
     * @param \DOMNode $parent
     * @param Model\Transaction[] $transactions
     * @param Model\AccountInfo $accountInfo
     * @param Model\Period $period
     * @return void
     * @throws \DOMException
     */
    private function addNotification($parent, array $transactions, Model\AccountInfo $accountInfo, Model\Period $period)
    {
        $notification = $this->xmlDoc->createElement('Ntfctn');
        $parent->appendChild($notification);

        // Add notification details
        $notification->appendChild($this->xmlDoc->createElement('Id', uniqid('NOTIF')));
        $notification->appendChild($this->xmlDoc->createElement('ElctrncSeqNb', '1'));
        $notification->appendChild($this->createDateTimeNode('CreDtTm'));

        // Add date range
        $frToDt = $this->xmlDoc->createElement('FrToDt');
        $notification->appendChild($frToDt);
        $frToDt->appendChild($this->createDateTimeNode('FrDtTm', $period->fromDate));
        $frToDt->appendChild($this->createDateTimeNode('ToDtTm', $period->toDate));

        // Add account information
        $this->addAccountInformation($notification, $accountInfo);

        // Add entries for each transaction
        foreach ($transactions as $transaction) {
            $this->addEntry($notification, $transaction);
        }
    }

    private function addAccountInformation($parent, Model\AccountInfo $accountInfo)
    {
        $acct = $this->xmlDoc->createElement('Acct');
        $parent->appendChild($acct);

        $id = $this->xmlDoc->createElement('Id');
        $acct->appendChild($id);

        // Add IBAN or other format of account number
        if (is_numeric(substr($accountInfo->accountNumber, 0, 2))) {
            $otherId = $this->xmlDoc->createElement('Othr');
            $otherId->appendChild($this->xmlDoc->createElement('Id', $accountInfo->accountNumber));
            $id->appendChild($otherId);
        } else {
            $id->appendChild($this->xmlDoc->createElement('IBAN', $accountInfo->accountNumber));
        }

        // Add currency
        $acct->appendChild($this->xmlDoc->createElement('Ccy', 'USD'));

        // Add owner information
        $owner = $this->xmlDoc->createElement('Ownr');
        $acct->appendChild($owner);
        $owner->appendChild($this->xmlDoc->createElement('Nm', $accountInfo->ownerName));
    }

    private function addEntry($parent, Model\Transaction $transaction)
    {
        $entry = $this->xmlDoc->createElement('Ntry');
        $parent->appendChild($entry);

        // Entry reference
        $entry->appendChild($this->xmlDoc->createElement('NtryRef', $this->formatId($transaction->id)));

        // Amount
        $amt = $this->xmlDoc->createElement('Amt', (string)abs($transaction->amount));
        $amt->setAttribute('Ccy', 'USD');
        $entry->appendChild($amt);

        // Credit/Debit indicator
        $entry->appendChild($this->xmlDoc->createElement('CdtDbtInd', $transaction->amount > 0 ? 'CRDT' : 'DBIT'));

        // Status
        $entry->appendChild($this->xmlDoc->createElement('Sts', 'BOOK'));

        // Booking Date
        $bookgDt = $this->xmlDoc->createElement('BookgDt');
        $entry->appendChild($bookgDt);
        $bookgDt->appendChild(
            $this->xmlDoc->createElement(
                'Dt',
                substr(
                    $transaction->postedAt->format(self::DT_FORMAT),
                    0,
                    10
                )
            )
        );

        // Value Date
        $valDt = $this->xmlDoc->createElement('ValDt');
        $entry->appendChild($valDt);
        $valDt->appendChild(
            $this->xmlDoc->createElement(
                'Dt',
                substr(
                    $transaction->createdAt->format(self::DT_FORMAT),
                    0,
                    10
                )
            )
        );

        // Bank Transaction Code
        $this->addBankTransactionCode($entry);

        // Entry Details
        $this->addEntryDetails($entry, $transaction);
    }

    private static function formatId($id)
    {
        if (strlen($id) <= 35) {
            return $id;
        }
        $alphanumeric = preg_replace('/[^a-zA-Z0-9]/', '', $id);
        return strlen($alphanumeric) <= 35 ? $alphanumeric : md5($id);
    }

    private function addBankTransactionCode($parent)
    {
        $bankTxCode = $this->xmlDoc->createElement('BkTxCd');
        $parent->appendChild($bankTxCode);

        $domain = $this->xmlDoc->createElement('Domn');
        $bankTxCode->appendChild($domain);
        $domain->appendChild($this->xmlDoc->createElement('Cd', 'PMNT'));

        $family = $this->xmlDoc->createElement('Fmly');
        $domain->appendChild($family);
        $family->appendChild($this->xmlDoc->createElement('Cd', 'RCDT'));
        $family->appendChild($this->xmlDoc->createElement('SubFmlyCd', 'VCOM'));
    }

    private function addEntryDetails($parent, $transaction)
    {
        $entryDtls = $this->xmlDoc->createElement('NtryDtls');
        $parent->appendChild($entryDtls);

        // Transaction Details
        $txDtls = $this->xmlDoc->createElement('TxDtls');
        $entryDtls->appendChild($txDtls);

        // References
        $refs = $this->xmlDoc->createElement('Refs');
        $txDtls->appendChild($refs);
        $refs->appendChild($this->xmlDoc->createElement('EndToEndId', $this->formatId($transaction->id)));

        // Amount Details
        $amt = $this->xmlDoc->createElement('Amt', (string)abs($transaction->amount));
        $amt->setAttribute('Ccy', 'USD');
        $txDtls->appendChild($amt);

        // Credit/Debit Indicator
        $txDtls->appendChild($this->xmlDoc->createElement('CdtDbtInd', $transaction->amount > 0 ? 'CRDT' : 'DBIT'));

        // Related Parties
        $this->addRelatedParties($txDtls, $transaction);

        // Remittance Information
        if (!empty($transaction->note)) {
            $rmtInf = $this->xmlDoc->createElement('RmtInf');
            $txDtls->appendChild($rmtInf);
            $rmtInf->appendChild($this->xmlDoc->createElement('Ustrd', $transaction->note));
        }
    }

    private function addRelatedParties($parent, $transaction)
    {
        $relatedParties = $this->xmlDoc->createElement('RltdPties');
        $parent->appendChild($relatedParties);

        // Add debtor/creditor information based on transaction type
        if ($transaction->amount > 0) {
            $debtor = $this->xmlDoc->createElement('Dbtr');
            $relatedParties->appendChild($debtor);
            $debtor->appendChild($this->xmlDoc->createElement('Nm', $transaction->counterpartyName));
        } else {
            $creditor = $this->xmlDoc->createElement('Cdtr');
            $relatedParties->appendChild($creditor);
            $creditor->appendChild($this->xmlDoc->createElement('Nm', $transaction->counterpartyName));
        }
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
