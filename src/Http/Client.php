<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Http;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @var GuzzleClient $client respecting GuzzleClient @final annotation and testability
     */
    private GuzzleClient $client;

    /**
     * @param GuzzleClient $client
     */
    public function __construct(GuzzleClient $client = new GuzzleClient())
    {
        $this->client = $client;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->client->get($uri, $options);
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $options
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->client->post($uri, $options);
    }
}