<?php

namespace App;

use App\DataSource\Mercury;

class Cli
{
    private $defaultFromDate = '-2 months'; // to cover at least one full statement which usually covers previous month
    private $defaultToDate = 'now';
    private $defaultCurrency = 'USD';

    public function __construct()
    {
    }

    public function execute()
    {
        try {
            $dotenv = \Dotenv\Dotenv::createImmutable(BASE_DIR);
            $dotenv->load();

            if (!isset($_ENV['MERCURY_TOKEN'])) {
                throw new \UnderflowException("MERCURY_TOKEN is not defined in .env");
            }

            $dataSource = new Mercury($_ENV['MERCURY_TOKEN']);

            if (!isset($_ENV['COMPANY_NAME'])) {
                echo "COMPANY_NAME is not defined in .env, please setup\n";
            }

            $formats = [
                'CAMT54' => TargetFormat\Camt054_1_04::class
            ];

            if (!isset($_ENV['OUTPUT_FORMAT']) || !array_key_exists($_ENV['OUTPUT_FORMAT'], $formats)) {
                throw new \UnderflowException("OUTPUT_FORMAT is not defined in .env, setup one of these:\n"
                    . implode("\n", array_map(fn($item) => "OUTPUT_FORMAT={$item}", array_keys($formats)))
                );
            }

            if (!isset($_ENV['MERCURY_ACCOUNT_ID']) || !isset($_ENV['MERCURY_ACCOUNT_NUMBER'])) {
                $accounts = $dataSource->listAccounts();
                foreach ($accounts as $account) {
                    echo "#MERCURY_ACCOUNT_NAME=\"{$account->name}\"\n";
                    echo "MERCURY_ACCOUNT_ID={$account->id}\n";
                    echo "MERCURY_ACCOUNT_NUMBER={$account->accountNumber}\n";
                    if (!isset($_ENV['COMPANY_NAME'])) {
                        echo "COMPANY_NAME=\"{$account->legalBusinessName}\"\n";
                    }
                    echo "\n";
                }
                throw new \UnderflowException( "MERCURY_ACCOUNT_ID or MERCURY_ACCOUNT_NUMBER is not found, please setup one of details above to the .env");
            }

            $accountInfo = new Model\AccountInfo(
                $_ENV['MERCURY_ACCOUNT_ID'],
                $_ENV['MERCURY_ACCOUNT_NUMBER'],
                $_ENV['COMPANY_NAME'],
                $_ENV['MERCURY_ACCOUNT_CURRENCY'] ?? $this->defaultCurrency
            );
            $period = new Model\Period(
                new \DateTimeImmutable($_ENV['MERCURY_FROM_DATE'] ?? $this->defaultFromDate),
                new \DateTimeImmutable($_ENV['MERCURY_TO_DATE'] ?? $this->defaultToDate),
            );

            $transactions = $dataSource->getTransactions($accountInfo->accountId, $period->fromDate, $period->toDate);

            $generator = new $formats[$_ENV['OUTPUT_FORMAT']]();

            $xml = $generator->generateFromTransactions($transactions, $accountInfo, $period);

            if (isset($_ENV['OUTPUT_FILE'])) {
                if (touch($_ENV['OUTPUT_FILE']) && is_writable($_ENV['OUTPUT_FILE'])) {
                    file_put_contents($_ENV['OUTPUT_FILE'], $xml);
                    echo "Successfully saved to {$_ENV['OUTPUT_FILE']}!\n";
                } else {
                    throw new \LogicException("{$_ENV['OUTPUT_FILE']} is not writable, check it's folder permissions.");
                }
            } else {
                echo $xml;
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
            exit ($e->getCode() ?? -1);
        }
    }
}