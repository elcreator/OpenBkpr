<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

enum TransactionKind: string
{
    case EXTERNAL_TRANSFER = 'externalTransfer';
    case INTERNAL_TRANSFER = 'internalTransfer';
    case OUTGOING_PAYMENT = 'outgoingPayment';
    case CREDIT_CARD_CREDIT = 'creditCardCredit';
    case CREDIT_CARD_TRANSACTION = 'creditCardTransaction';
    case DEBIT_CARD_TRANSACTION = 'debitCardTransaction';
    case INCOMING_DOMESTIC_WIRE = 'incomingDomesticWire';
    case CHECK_DEPOSIT = 'checkDeposit';
    case INCOMING_INTERNATIONAL_WIRE = 'incomingInternationalWire';
    case TREASURY_TRANSFER = 'treasuryTransfer';
    case CARD_INTERNATIONAL_TRANSACTION_FEE = 'cardInternationalTransactionFee';
    case WIRE_FEE = 'wireFee';
    case OTHER = 'other';
}