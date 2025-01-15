<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

enum AttachmentType: string
{
    case CHECK_IMAGE = 'checkImage';
    case RECEIPT = 'receipt';
    case OTHER = 'other';
}