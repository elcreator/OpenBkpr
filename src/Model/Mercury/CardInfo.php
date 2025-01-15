<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Model\Mercury;

readonly class CardInfo
{
    public function __construct(
        public string $id
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
        );
    }
}