<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

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

            if (!isset($_ENV['COMPANY_NAME'])) {
                echo "COMPANY_NAME is not defined in .env, please setup\n";
            }

            if (!isset($_ENV['SOURCES'])) {
                echo "SOURCES is not defined in .env, please setup\n";
            }

            $allFormats = [
                'CAMT54' => TargetFormat\Camt054_1_04::class
            ];
            if (!isset($_ENV['OUTPUT_FORMAT']) || !array_key_exists($_ENV['OUTPUT_FORMAT'], $allFormats)) {
                throw new \UnderflowException("OUTPUT_FORMAT is not defined in .env, setup one of these:\n"
                    . implode("\n", array_map(fn($item) => "OUTPUT_FORMAT={$item}", array_keys($allFormats)))
                );
            }
            $format = $_ENV['OUTPUT_FORMAT'];

            $allSources = [DataSource\Mercury::CONFIG_NAME, DataSource\Stripe::CONFIG_NAME];
            if (!isset($_ENV['SOURCES'])) {
                throw new \UnderflowException("SOURCES is not defined in .env, setup any of these comma-separated:\n"
                    . implode(",", $allSources)
                );
            }
            $sources = explode(',', $_ENV['SOURCES']);

            foreach ($sources as $source) {
                if (!in_array($source, $allSources)) {
                    $this->outError("$source is not a valid source, skipping...");
                    continue;
                }
                $period = $this->getPeriod($source);
                $xml = '';
                /** @var TargetFormat\AbstractTargetFormat $generator */
                $generator = new $allFormats[$format]();
                if ($source === DataSource\Mercury::CONFIG_NAME) {
                    $accountInfo = $this->getMercuryAccountInfo();
                    $transactions = $this->getMercuryTransactions($period, $accountInfo);
                    $xml = $generator->generateFromTransactions($transactions, $accountInfo, $period);
                } else if ($source === DataSource\Stripe::CONFIG_NAME) {
                    $accountId = $_ENV['STRIPE_ACCOUNT_ID'];
                    $stripe = new DataSource\Stripe($_ENV['STRIPE_TOKEN']);
                    $transactions = $stripe->getTransactions($accountId, $period);
                    $xml = $generator->generateFromTransactions($transactions, new Model\AccountInfo($accountId,
                        $_ENV['STRIPE_ACCOUNT_NUMBER'] ?? '', $_ENV['COMPANY_NAME'] ?? '',
                        $_ENV['STRIPE_CURRENCY'] ?? ''), $period);
                }
                if (!$xml) {
                    $this->outError("Nothing was generated for $source!");
                    continue;
                }
                if (isset($_ENV['OUTPUT_FILE'])) {
                    $this->outputToFile($xml, $this->outputFileName($period, $source, $format, $_ENV['OUTPUT_FILE']));
                } else {
                    echo $xml;
                }
            }
        } catch (\Exception $e) {
            $this->outError($e->getMessage());
            exit ($e->getCode() ?? -1);
        }
    }

    public function getMercuryTransactions($period, $accountInfo)
    {
        $dataSource = new Mercury($_ENV['MERCURY_TOKEN']);

        if (!isset($_ENV['MERCURY_TOKEN'])) {
            throw new \UnderflowException("MERCURY_TOKEN is not defined in .env");
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
            throw new \UnderflowException("MERCURY_ACCOUNT_ID or MERCURY_ACCOUNT_NUMBER is not found, please setup one of details above to the .env");
        }

        return $dataSource->getTransactions($period->fromDate, $period->toDate, $accountInfo->accountId);
    }

    private function getPeriod($source)
    {
        $sourcePrefix = strtoupper($source) . '_';
        return new Model\Period(
            new \DateTimeImmutable($_ENV[$sourcePrefix . 'FROM_DATE'] ?? $_ENV['FROM_DATE'] ?? $this->defaultFromDate),
            new \DateTimeImmutable($_ENV[$sourcePrefix . 'TO_DATE'] ?? $_ENV['TO_DATE'] ?? $this->defaultToDate),
        );
    }

    private function getMercuryAccountInfo()
    {
        return new Model\AccountInfo(
            $_ENV['MERCURY_ACCOUNT_ID'],
            $_ENV['MERCURY_ACCOUNT_NUMBER'],
            $_ENV['COMPANY_NAME'],
            $_ENV['MERCURY_ACCOUNT_CURRENCY'] ?? $this->defaultCurrency
        );
    }

    private function outputFileName(Model\Period $period, $source, $format, $template)
    {
        return str_replace(
            ['{source}', '{period}', '{format}'],
            [$source, $period->fromDate->format('Y-m-d') . '-' . $period->toDate->format('Y-m-d'), $format],
            $template
        );
    }

    private function outputToFile($xml, $filename)
    {
        if (touch($filename) && is_writable($filename)) {
            file_put_contents($filename, $xml);
            echo "Successfully saved to $filename!\n";
        } else {
            throw new \LogicException("{$filename} is not writable, check it's folder permissions.");
        }
    }

    private function outError($text)
    {
        fwrite(STDERR, $text . PHP_EOL);
    }
}