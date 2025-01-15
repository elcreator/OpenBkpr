<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\PayPal;

enum PayPalTransactionStatus: string
{
    case SUCCESS = 'S';
    case PENDING = 'P';
    case FAILED = 'F';
}
