<?php

namespace App\DataSource;
use App\Model;

class Mercury
{
    private int $pageSize = 10;
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
        $result = json_decode($response->getBody());
        if (!is_array($result?->accounts)) {
            throw new \LogicException('Invalid response, missing accounts');
        }
        return $result->accounts;
    }

    public function getTransactions(string $accountId, \DateTimeImmutable $fromDate, \DateTimeImmutable $toDate)
    {
        $transactionsPage = $this->getTransactionsPage($accountId, $fromDate, $toDate, 0);
        echo $transactionsPage['total'];
        return array_map(fn($transaction) => Model\Transaction::fromArray($transaction), $transactionsPage['transactions']);
    }

    public function getTransactionsPage(string $accountId, \DateTimeImmutable $fromDate, \DateTimeImmutable $toDate, int $page) {
        $response = $this->client->get(self::API_BASE_URL . "account/{$accountId}/transactions?limit={$this->pageSize}"
            . "&offset={$page}&start={$fromDate->format('Y-m-d')}&end={$toDate->format('Y-m-d')}",
            $this->requestOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody(), true);
        if (!is_integer($result['total']) || !is_array($result['transactions'])) {
            throw new \LogicException('Invalid response');
        }
        return $result;
    }
}