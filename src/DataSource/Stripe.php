<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;

use App\Model;
use App\Http\ClientInterface;

class Stripe extends AbstractDataSource
{
    public const CONFIG_NAME = 'Stripe';
    const API_BASE_URL = 'https://api.stripe.com/v1/';
    private int $pageSize = 50;
    private ?string $lastId = null;

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
        $response = $this->client->get(self::API_BASE_URL . 'financial_connections/accounts', $this->requestOptions);
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents());
        if (!is_array($result?->data)) {
            throw new \LogicException('Invalid response, missing accounts');
        }
        return $result->data;
    }

    /**
     * @param Model\Period $period
     * @return \App\Model\Transaction[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTransactions(Model\Period $period): array
    {
        $result = [];
        do {
            $transactionsPage = $this->getTransactionsPage();
            $result = array_merge(
                $result,
                array_map(fn($transaction) => Model\Stripe\BalanceTransaction::fromArray(
                    $transaction
                )->toTransaction(),
                    $transactionsPage['data'])
            );
        } while ($transactionsPage['has_more'] === true);
        return $result;
    }

    /**
     * https://docs.stripe.com/api/balance_transactions/list
     * @return array<string, mixed>
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function getTransactionsPage(): array
    {
        $response = $this->client->get(
            self::API_BASE_URL . "balance_transactions?limit={$this->pageSize}"
            . (is_null($this->lastId) ? '' : "&starting_after={$this->lastId}"),
            $this->requestOptions
        );
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents(), true);
        if (!is_bool($result['has_more']) || !is_array($result['data'])) {
            throw new \LogicException('Invalid response');
        }
        $this->lastId = empty($result['data']) ? null : $result['data'][count($result['data']) - 1]['id'];
        return $result;
    }
}