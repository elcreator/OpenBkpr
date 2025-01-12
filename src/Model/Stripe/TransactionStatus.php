<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Stripe;

enum TransactionStatus: string
{
    case AVAILABLE = 'available';
    case PENDING = 'pending';
}
