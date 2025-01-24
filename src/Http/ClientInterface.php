<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;

interface ClientInterface
{
    /**
     * @param string $uri
     * @param array<string, mixed> $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function get(string $uri, array $options = []): ResponseInterface;

    /**
     * @param string $uri
     * @param array<string, mixed> $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function post(string $uri, array $options = []): ResponseInterface;
}