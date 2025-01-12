<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;
use App\Model;

class Stripe
{
    public const CONFIG_NAME = 'Stripe';
    private int $pageSize = 5;
    private ?string $lastId = null;
    private array $requestOptions = [
        'headers' => [
            'accept' => 'application/json',
        ],
    ];
    const API_BASE_URL = 'https://api.stripe.com/v1/';

    private \Psr\Http\Client\ClientInterface $client;

    public function __construct(string $token, $client = new \GuzzleHttp\Client())
    {
        $this->requestOptions['headers']['Authorization'] = "Bearer {$token}";
        $this->client = $client;
    }

    public function listAccounts()
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions(Model\Period $period)
    {
        $result = [];
        $page = 0;
        do {
            $transactionsPage = $this->getTransactionsPage($page++);
            $result = array_merge($result, array_map(fn($transaction) => Model\Stripe\BalanceTransaction::fromArray(
                $transaction)->toTransaction(),
                $transactionsPage['data']));
        } while ($transactionsPage['has_more'] === true);
        return $result;
    }

    /**
     * https://docs.stripe.com/api/balance_transactions/list
     * @param int $page
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getTransactionsPage(int $page) {
        $response = $this->client->get(self::API_BASE_URL . "balance_transactions?limit={$this->pageSize}"
            . (is_null($this->lastId) ? '' : "&starting_after={$this->lastId}"),
            $this->requestOptions);
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