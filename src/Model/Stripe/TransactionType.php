<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Stripe;

enum TransactionType: string
{
    /**
     * Adjustments correspond to additions or deductions from your Stripe balance that are made outside of the normal
     * charge/refund flow. For example, some of the most common reasons for adjustments are:
     * * Refund failures. If your customer’s bank or card issuer is unable to correctly process a refund
     * (e.g., due to a closed bank account or a problem with the card) Stripe returns the funds to your balance.
     * The returned funds are represented as a Balance transaction with the type adjustment, where the description
     * indicates the related refund object.
     * * Disputes. When a customer disputes a charge, Stripe deducts the disputed amount from your balance.
     * The deduction is represented as a Balance transaction with the type adjustment, where the source object is a dispute.
     * * Dispute reversals. When you win a dispute, the disputed amount is returned to your balance.
     * The returned funds are represented as a Balance transaction with the type adjustment, where the source object is a dispute.
     * In the past, fees for Stripe software and services (e.g., for Radar, Connect and Billing) were represented as adjustments.
     * In the past, Connect platform fee refunds were represented as adjustments.
     * The description field on the Balance transaction describes the purpose of each adjustment.
     */
    case ADJUSTMENT = 'adjustment';
    /**
     * Incrementing available funds for instant payouts. This occurs when you create an instant payout and the requested
     * payout amount is greater than your connected account's available balance. Funds are added to your available
     * balance and removed from your pending balance to cover the difference.
     */
    case ADVANCE = 'advance';
    /**
     * Decrementing pending funds for instant payouts. This occurs when you create an instant payout and the requested
     * payout amount is greater than your connected account's available balance. Funds are added to your available
     * balance and removed from your pending balance to cover the difference.
     */
    case ADVANCE_FUNDING = 'advance_funding';
    /**
     * Repayments made to service an anticipation loan in Brazil. These repayments go to the financial institution to
     * whom you have sold your receivables.
     */
    case ANTICIPATION_REPAYMENT = 'anticipation_repayment';
    /**
     * Earnings you've generated by collecting platform fees via Stripe Connect charges.
     */
    case APPLICATION_FEE = 'application_fee';
    /**
     * Platform fees that you have returned to your connected accounts.
     */
    case APPLICATION_FEE_REFUND = 'application_fee_refund';
    /**
     * Created when a credit card charge is created successfully.
     */
    case CHARGE = 'charge';
    /**
     * Funds used to purchase carbon removal units from Frontier Climate.
     */
    case CLIMATE_ORDER_PURCHASE = 'climate_order_purchase';
    /**
     * Funds refunded to your balance when a Climate Order is canceled.
     */
    case CLIMATE_ORDER_REFUND = 'climate_order_refund';
    /**
     * If one of your connected accounts has a negative balance for 180 days, Stripe transfers a portion of your balance,
     * to zero out that account's balance.
     */
    case CONNECT_COLLECTION_TRANSFER = 'connect_collection_transfer';
    /**
     * Funds contributed via Stripe to a cause (currently Stripe Climate).
     */
    case CONTRIBUTION = 'contribution';
    /**
     * When an issued card is used to make a purchase, an authorization is created. If approved, this holds the authorized
     * amount in reserve from your account balance, until captured or voided. Merchants can update authorization to
     * request additional amounts.
     */
    case ISSUING_AUTHORIZATION_HOLD = 'issuing_authorization_hold';
    /**
     * When an authorized purchase is captured by the merchant, the funds previously held for the authorization are
     * released. Simultaneously, an issuing transaction is created, and the purchase amount is deducted from your
     * Stripe balance.
     */
    case ISSUING_AUTHORIZATION_RELEASE = 'issuing_authorization_release';
    /**
     * When you dispute an Issuing transaction and funds return to your Stripe balance.
     */
    case ISSUING_DISPUTE = 'issuing_dispute';
    /**
     * When an authorized purchase has been authorized and captured by the merchant, an issuing transaction is created,
     * and the purchase amount is deducted from your Stripe balance.
     */
    case ISSUING_TRANSACTION = 'issuing_transaction';
    /**
     * Obligation for receivable unit received.
     */
    case OBLIGATION_OUTBOUND = 'obligation_outbound';
    /**
     * Obligation for receivable unit reversed.
     */
    case OBLIGATION_REVERSAL_INBOUND = 'obligation_reversal_inbound';
    /**
     * Created when a local payment method charge is created successfully.
     */
    case PAYMENT = 'payment';
    /**
     * ACH, direct debit, and other delayed notification payment methods remain in a pending state until they either
     * succeed or fail. You'll see a pending Balance transaction of type payment when the payment is created.
     * Another Balance transaction of type payment_failure_refund appears if the pending payment later fails.
     */
    case PAYMENT_FAILURE_REFUND = 'payment_failure_refund';
    /**
     * Funds that a payment network holds in reserve (e.g. to mitigate risk).
     */
    case PAYMENT_NETWORK_RESERVE_HOLD = 'payment_network_reserve_hold';
    /**
     * Funds that a payment network releases from a reserve.
     */
    case PAYMENT_NETWORK_RESERVE_RELEASE = 'payment_network_reserve_release';
    /**
     * Created when a local payment method refund is initiated. Additionally, if your customer's bank or card issuer
     * is unable to correctly process a refund (e.g., due to a closed bank account or a problem with the card)
     * Stripe returns the funds to your balance.
     */
    case PAYMENT_REFUND = 'payment_refund';
    /**
     * Created when a debit/failure related to a payment is detected from a banking partner. This balance transaction
     * takes funds that were previously credited to the merchant for a payment out of the merchant balance.
     */
    case PAYMENT_REVERSAL = 'payment_reversal';
    /**
     * Created when a customer has unreconciled funds within Stripe for more than ninety days. This balance transaction
     * transfers those funds to your balance.
     */
    case PAYMENT_UNRECONCILED = 'payment_unreconciled';
    /**
     * Payouts from your Stripe balance to your bank account.
     */
    case PAYOUT = 'payout';
    /**
     * Created when a payout to your bank account is cancelled and the funds are returned to your Stripe balance.
     */
    case PAYOUT_CANCEL = 'payout_cancel';
    /**
     * Created when a payout to your bank account fails and the funds are returned to your Stripe balance.
     */
    case PAYOUT_FAILURE = 'payout_failure';
    /**
     * Minimum balance held from a payout.
     */
    case PAYOUT_MINIMUM_BALANCE_HOLD = 'payout_minimum_balance_hold';
    /**
     * Minimum balance released after a payout.
     */
    case PAYOUT_MINIMUM_BALANCE_RELEASE = 'payout_minimum_balance_release';
    /**
     * Created when a credit card charge refund is initiated. If you authorize and capture separately and the capture
     * amount is less than the initial authorization, you see a balance transaction of type charge for the full
     * authorization amount and another balance transaction of type refund for the uncaptured portion.
     */
    case REFUND = 'refund';
    /**
     * Created when a credit card charge refund fails, and Stripe returns the funds to your balance. This may occur if
     * your customer's bank or card issuer is unable to correctly process a refund (e.g., closed bank account or card
     * problem).
     */
    case REFUND_FAILURE = 'refund_failure';
    /**
     * If one of your connected accounts' balances becomes negative, Stripe temporarily reserves a portion of your
     * balance to ensure that funds can be covered. Released when balance becomes less negative.
     */
    case RESERVE_TRANSACTION = 'reserve_transaction';
    /**
     * When Stripe holds your funds in reserve to mitigate risk, two balance transactions are created: one to debit
     * the funds from your balance, and a second to credit the funds back to your balance at the end of the reserve
     * period.
     */
    case RESERVED_FUNDS = 'reserved_funds';
    /**
     * Fees for Stripe software and services (e.g., for Radar, Connect, Billing, and Identity).
     */
    case STRIPE_FEE = 'stripe_fee';
    /**
     * Stripe currency conversion fee
     */
    case STRIPE_FX_FEE = 'stripe_fx_fee';
    /**
     * Taxes collected by Stripe to be remitted to the appropriate local governments. Typically, this is a tax on
     * Stripe fees.
     */
    case TAX_FEE = 'tax_fee';
    /**
     * Funds you transferred into your Stripe balance from your bank account.
     */
    case TOPUP = 'topup';
    /**
     * If an initially successful top-up fails or is cancelled, the credit to your Stripe balance is reversed.
     */
    case TOPUP_REVERSAL = 'topup_reversal';
    /**
     * Funds sent from your balance to the balance of your connected accounts.
     */
    case TRANSFER = 'transfer';
    /**
     * Transfers to your connected accounts that have been cancelled.
     */
    case TRANSFER_CANCEL = 'transfer_cancel';
    /**
     * Transfers to your connected accounts that failed. Transfer failures add to your platform's balance and subtract
     * from the connected account's balance.
     */
    case TRANSFER_FAILURE = 'transfer_failure';
    /**
     * Transfers to your connected accounts that you reversed or that were reversed as a result of a failure in
     * payments made through ACH, direct debit, and other delayed notification payment methods.
     */
    case TRANSFER_REFUND = 'transfer_refund';
}