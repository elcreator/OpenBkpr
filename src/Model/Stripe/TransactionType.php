<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Stripe;

enum TransactionType: string
{
    case ADJUSTMENT = 'adjustment';
    case ADVANCE = 'advance';
    case ADVANCE_FUNDING = 'advance_funding';
    case ANTICIPATION_REPAYMENT = 'anticipation_repayment';
    case APPLICATION_FEE = 'application_fee';
    case APPLICATION_FEE_REFUND = 'application_fee_refund';
    case CHARGE = 'charge';
    case CLIMATE_ORDER_PURCHASE = 'climate_order_purchase';
    case CLIMATE_ORDER_REFUND = 'climate_order_refund';
    case CONNECT_COLLECTION_TRANSFER = 'connect_collection_transfer';
    case CONTRIBUTION = 'contribution';
    case ISSUING_AUTHORIZATION_HOLD = 'issuing_authorization_hold';
    case ISSUING_AUTHORIZATION_RELEASE = 'issuing_authorization_release';
    case ISSUING_DISPUTE = 'issuing_dispute';
    case ISSUING_TRANSACTION = 'issuing_transaction';
    case OBLIGATION_OUTBOUND = 'obligation_outbound';
    case OBLIGATION_REVERSAL_INBOUND = 'obligation_reversal_inbound';
    case PAYMENT = 'payment';
    case PAYMENT_FAILURE_REFUND = 'payment_failure_refund';
    case PAYMENT_NETWORK_RESERVE_HOLD = 'payment_network_reserve_hold';
    case PAYMENT_NETWORK_RESERVE_RELEASE = 'payment_network_reserve_release';
    case PAYMENT_REFUND = 'payment_refund';
    case PAYMENT_REVERSAL = 'payment_reversal';
    case PAYMENT_UNRECONCILED = 'payment_unreconciled';
    case PAYOUT = 'payout';
    case PAYOUT_CANCEL = 'payout_cancel';
    case PAYOUT_FAILURE = 'payout_failure';
    case PAYOUT_MINIMUM_BALANCE_HOLD = 'payout_minimum_balance_hold';
    case PAYOUT_MINIMUM_BALANCE_RELEASE = 'payout_minimum_balance_release';
    case REFUND = 'refund';
    case REFUND_FAILURE = 'refund_failure';
    case RESERVE_TRANSACTION = 'reserve_transaction';
    case RESERVED_FUNDS = 'reserved_funds';
    case STRIPE_FEE = 'stripe_fee';
    case STRIPE_FX_FEE = 'stripe_fx_fee';
    case TAX_FEE = 'tax_fee';
    case TOPUP = 'topup';
    case TOPUP_REVERSAL = 'topup_reversal';
    case TRANSFER = 'transfer';
    case TRANSFER_CANCEL = 'transfer_cancel';
    case TRANSFER_FAILURE = 'transfer_failure';
    case TRANSFER_REFUND = 'transfer_refund';
}
