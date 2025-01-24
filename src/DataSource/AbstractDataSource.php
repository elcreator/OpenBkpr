<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;

use App\Http\ClientInterface;

abstract class AbstractDataSource
{
    /**
     * @var array<string,array<string, string>>
     */
    protected array $requestOptions = [
        'headers' => [
            'accept' => 'application/json',
        ],
    ];

    protected ClientInterface $client;

    abstract function __construct(string $token, ClientInterface $client);
}