<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class Attachment
{
    public function __construct(
        public string $fileName,
        public string $url,
        public AttachmentType $attachmentType
    ) {
    }
}

