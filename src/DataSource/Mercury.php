<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;

use App\Model;
use App\Http\ClientInterface;

class Mercury extends AbstractDataSource
{
    public const CONFIG_NAME = 'Mercury';
    const API_BASE_URL = 'https://api.mercury.com/api/v1/';
    private int $pageSize = 500;

    public function __construct(string $token, ClientInterface $client = new \App\Http\Client())
    {
        $this->requestOptions['headers']['Authorization'] = "Bearer {$token}";
        $this->client = $client;
    }

    /**
     * @return array<string, mixed>
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function listAccounts(): array
    {
        $response = $this->client->get(self::API_BASE_URL . 'accounts', $this->requestOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents());
        if (!is_array($result?->accounts)) {
            throw new \LogicException('Invalid response, missing accounts');
        }
        return $result->accounts;
    }

    /**
     * @param string $accountId
     * @return \App\Model\Transaction[]
     */
    public function getTransactions(Model\Period $period, string $accountId): array
    {
        $result = [];
        $page = 0;
        do {
            $transactionsPage = $this->getTransactionsPage($period->fromDate, $period->toDate, $accountId, $page++);
            $result = array_merge(
                $result,
                array_map(fn($transaction) => Model\Mercury\Transaction::fromArray(
                    $transaction
                )->toTransaction(),
                    $transactionsPage['transactions'])
            );
        } while ($transactionsPage['total'] > 0);
        return $result;
    }

    /**
     * @param \DateTimeImmutable $fromDate
     * @param \DateTimeImmutable $toDate
     * @param string $accountId
     * @param int $page
     * @return array<string, mixed>
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function getTransactionsPage(
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
        string $accountId,
        int $page
    ): array {
        $offset = $page * $this->pageSize;
        $response = $this->client->get(
            self::API_BASE_URL . "account/{$accountId}/transactions?limit={$this->pageSize}"
            . "&offset={$offset}&start={$fromDate->format('Y-m-d')}&end={$toDate->format('Y-m-d')}",
            $this->requestOptions
        );
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