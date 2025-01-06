<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;
use App\Model;

class Mercury
{
    private int $pageSize = 500;
    private array $requestOptions = [
        'headers' => [
            'accept' => 'application/json',
        ],
    ];
    const API_BASE_URL = 'https://api.mercury.com/api/v1/';
    private \Psr\Http\Client\ClientInterface $client;

    public function __construct(string $token, $client = new \GuzzleHttp\Client())
    {
        $this->requestOptions['headers']['Authorization'] = "Bearer {$token}";
        $this->client = $client;
    }

    public function listAccounts()
    {
        $response = $this->client->get(self::API_BASE_URL . "accounts", $this->requestOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents());
        if (!is_array($result?->accounts)) {
            throw new \LogicException('Invalid response, missing accounts');
        }
        return $result->accounts;
    }

    public function getTransactions(string $accountId, \DateTimeImmutable $fromDate, \DateTimeImmutable $toDate)
    {
        $result = [];
        $page = 0;
        do {
            $transactionsPage = $this->getTransactionsPage($accountId, $fromDate, $toDate, $page++);
            $result = array_merge($result, array_map(fn($transaction) => Model\Transaction::fromArray($transaction),
                $transactionsPage['transactions']));
        } while ($transactionsPage['total'] > 0);
        return $result;
    }

    public function getTransactionsPage(string $accountId, \DateTimeImmutable $fromDate, \DateTimeImmutable $toDate, int $page) {
        $offset = $page * $this->pageSize;
        $response = $this->client->get(self::API_BASE_URL . "account/{$accountId}/transactions?limit={$this->pageSize}"
            . "&offset={$offset}&start={$fromDate->format('Y-m-d')}&end={$toDate->format('Y-m-d')}",
            $this->requestOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents(), true);
        if (!is_integer($result['total']) || !is_array($result['transactions'])) {
            throw new \LogicException('Invalid response');
        }
        return $result;
    }
}