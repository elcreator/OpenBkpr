<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}