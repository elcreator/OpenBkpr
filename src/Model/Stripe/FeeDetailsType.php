<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Stripe;

enum FeeDetailsType: string
{
    case APPLICATION_FEE = 'application_fee';
    case PAYMENT_METHOD_PASSTHROUGH_FEE = 'payment_method_passthrough_fee';
    case STRIPE_FEE = 'stripe_fee';
    case TAX = 'tax';
}
