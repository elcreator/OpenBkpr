<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\DataSource;

use App\Model;

class PayPal
{
    public const CONFIG_NAME = 'PayPal';
    const API_BASE_URL = 'https://api-m.paypal.com/v1/';
    private int $pageSize = 10;
    private array $requestOptions = [
        'headers' => [
            'accept' => 'application/json',
        ],
    ];
    private \Psr\Http\Client\ClientInterface $client;

    public function __construct(string $token, $client = new \GuzzleHttp\Client())
    {
        $this->requestOptions['headers']['Authorization'] = "Bearer {$token}";
        $this->client = $client;
    }

    public static function login($clientId, $secret, $client = new \GuzzleHttp\Client())
    {
        $response = $client->post(
            self::API_BASE_URL .
            'oauth2/token',
            [
                'headers' => ['Authorization' => 'Basic ' . base64_encode("$clientId:$secret")],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]
        );
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param Model\Period $period
     * @return \App\Model\Transaction[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions(Model\Period $period)
    {
        $result = [];
        $from = $period->fromDate;
        $to = $period->toDate;
        while ($to <= $period->toDate) {
            $to = $from->modify('+30 days');
            $page = 1;
            do {
                $transactionsPage = $this->getTransactionsPage($from, $to, $page++);
                $result = array_merge(
                    $result,
                    array_map(fn($transaction) => Model\PayPal\Transaction::fromArray(
                        $transaction
                    )->toTransaction(),
                        $transactionsPage['transaction_details'])
                );
            } while ($transactionsPage['total_pages'] > $transactionsPage['page']);

            $from = $from->modify('+30 days');
        }
        return $result;
    }

    /**
     * requires https://uri.paypal.com/services/reporting/search/read
     * @param \DateTimeImmutable $fromDate
     * @param \DateTimeImmutable $toDate
     * @param int $page
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getTransactionsPage(
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
        int $page
    ) {
        $startDate = $fromDate->format('Y-m-d\TH:i:s\.v\Z');
        $endDate = $toDate->format('Y-m-d\TH:i:s\.v\Z');
        $response = $this->client->get(
            self::API_BASE_URL . "reporting/transactions?fields=all&start_date={$startDate}&end_date={$endDate}" .
            "&page_size={$this->pageSize}&page={$page}",
            $this->requestOptions
        );
        if ($response->getStatusCode() !== 200) {
            throw new \LogicException($response->getReasonPhrase());
        }
        $result = json_decode($response->getBody()->getContents(), true);
        if (!is_integer($result['total_pages']) || !is_array($result['transaction_details'])) {
            throw new \LogicException('Invalid response');
        }
        return $result;
    }
}