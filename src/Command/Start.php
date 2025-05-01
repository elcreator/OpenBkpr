<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\Command;

use App\DataSource;
use App\DataSource\Mercury;
use App\Model;
use App\TargetFormat;

/**
 * (or just run without any arguments) Starts execution assuming everything OK in .env file
 */
class Start implements CommandInterface
{
    private const ALL_SOURCES = [
        DataSource\Mercury::CONFIG_NAME,
        DataSource\Stripe::CONFIG_NAME,
        DataSource\PayPal::CONFIG_NAME,
    ];
    private string $defaultFromDate = '-2 months';
    private string $defaultToDate = 'now';
    private string $defaultCurrency = 'USD';
    private string $env;

    private DataSource\Factory $dataSourceFactory;
    private TargetFormat\Factory $targetFormatFactory;

    public function __construct(?DataSource\Factory $dataSourceFactory = null,
        ?TargetFormat\Factory $targetFormatFactory = null)
    {
        $this->dataSourceFactory = $dataSourceFactory ?? new DataSource\Factory();
        $this->targetFormatFactory = $targetFormatFactory ?? new TargetFormat\Factory();
    }

    public function run(): void
    {
        try {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->env ?? realpath(__DIR__ . '/../..'));
            $dotenv->load();

            if (!isset($_ENV['COMPANY_NAME'])) {
                $this->echoLine('COMPANY_NAME is not defined in .env, please setup');
            }

            if (!isset($_ENV['SOURCES'])) {
                $this->echoLine('SOURCES is not defined in .env, please setup');
            }
            $formats = $this->getConfigArray('OUTPUT_FORMATS', array_keys(TargetFormat\Factory::ALL_FORMATS));
            array_walk($formats, fn(&$value) => strtoupper($value));
            $sources = $this->getConfigArray('SOURCES', self::ALL_SOURCES);

            foreach ($sources as $source) {
                if (!in_array($source, self::ALL_SOURCES)) {
                    $this->outError("$source is not a valid source, skipping...");
                    continue;
                }
                $period = $this->getPeriod($source);

                if ($source === DataSource\Mercury::CONFIG_NAME) {
                    $accountInfo = $this->getMercuryAccountInfo();
                    $transactions = $this->getMercuryTransactions($period, $accountInfo);
                } elseif ($source === DataSource\Stripe::CONFIG_NAME) {
                    $accountInfo = new Model\AccountInfo(
                        '',
                        $_ENV['STRIPE_ACCOUNT_NUMBER'] ?? '', $_ENV['COMPANY_NAME'] ?? '',
                        $_ENV['STRIPE_CURRENCY'] ?? ''
                    );
                    $transactions = $this->getStripeTransactions($period);
                } elseif ($source === DataSource\PayPal::CONFIG_NAME) {
                    $accountInfo = new Model\AccountInfo(
                        '',
                        $_ENV['PAYPAL_ACCOUNT_NUMBER'] ?? '', $_ENV['COMPANY_NAME'] ?? '',
                        $_ENV['PAYPAL_CURRENCY'] ?? ''
                    );
                    $transactions = $this->getPayPalTransactions($period);
                } else {
                    continue;
                }

                foreach ($formats as $format) {
                    $generator = $this->targetFormatFactory->create($format);
                    $output = $generator->generateFromTransactions($transactions, $accountInfo, $period);
                    if (!$output) {
                        $this->outError("Nothing was generated for $source!");
                        continue;
                    }
                    if (isset($_ENV['OUTPUT_FILENAME'])) {
                        $this->outputToFile(
                            $output,
                            $this->outputFileName(
                                $period,
                                $source,
                                $generator->getExtension(),
                                $_ENV['OUTPUT_FILENAME']
                            )
                        );
                    } else {
                        echo $output;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->outError($e->getMessage(), $e->getCode() ?: -1);
        }
    }

    public function setEnv(string $env): void
    {
         $this->env = $env;
    }

    /**
     * @param string $envKey
     * @param string[] $allValues
     * @return string[]
     */
    private function getConfigArray(string $envKey, array $allValues): array
    {
        if (!isset($_ENV[$envKey])) {
            $allString = implode(",", $allValues);
            throw new \UnderflowException(
                "$envKey is not defined in .env, set it to any of these comma-separated values:\n$allString"
            );
        }
        $choices = explode(',', $_ENV[$envKey]);
        array_walk($choices, fn(&$value) => trim($value));
        $result = [];
        foreach ($choices as $choice) {
            if (!in_array($choice, $allValues)) {
                $this->outError("$choice is not a valid choice for $envKey, skipping...");
                continue;
            }
            $result[] = $choice;
        }
        return $result;
    }

    protected function outError(string $text, int|null $exitCode = null): void
    {
        fwrite(STDERR, $text . PHP_EOL);
        if (!is_null($exitCode)) {
            exit($exitCode);
        }
    }

    protected function echoLine(string $what): void {
        echo "$what\n";
    }

    private function getPeriod(string $source): Model\Period
    {
        $sourcePrefix = strtoupper($source) . '_';
        return new Model\Period(
            new \DateTimeImmutable($_ENV[$sourcePrefix . 'FROM_DATE'] ?? $_ENV['FROM_DATE'] ?? $this->defaultFromDate),
            new \DateTimeImmutable($_ENV[$sourcePrefix . 'TO_DATE'] ?? $_ENV['TO_DATE'] ?? $this->defaultToDate),
        );
    }

    protected function getMercuryAccountInfo(): Model\AccountInfo
    {
        return new Model\AccountInfo(
            $_ENV['MERCURY_ACCOUNT_ID'],
            $_ENV['MERCURY_ACCOUNT_NUMBER'],
            $_ENV['COMPANY_NAME'],
            $_ENV['MERCURY_ACCOUNT_CURRENCY'] ?? $this->defaultCurrency
        );
    }

    /**
     * @param Model\Period $period
     * @param Model\AccountInfo $accountInfo
     * @return Model\Transaction[]
     */
    public function getMercuryTransactions(Model\Period $period, Model\AccountInfo $accountInfo): array
    {
        $dataSource = $this->dataSourceFactory->createMercury();

        if (!isset($_ENV['MERCURY_ACCOUNT_ID']) || !isset($_ENV['MERCURY_ACCOUNT_NUMBER'])) {
            $accounts = $dataSource->listAccounts();
            foreach ($accounts as $account) {
                $this->echoLine("#MERCURY_ACCOUNT_NAME=\"{$account->name}\"");
                $this->echoLine("MERCURY_ACCOUNT_ID={$account->id}");
                $this->echoLine("MERCURY_ACCOUNT_NUMBER={$account->accountNumber}");
                if (!isset($_ENV['COMPANY_NAME'])) {
                    $this->echoLine("COMPANY_NAME=\"{$account->legalBusinessName}\"");
                }
            }
            throw new \UnderflowException(
                "MERCURY_ACCOUNT_ID or MERCURY_ACCOUNT_NUMBER is not found, "
                . "please setup one of details above to the .env"
            );
        }

        return $dataSource->getTransactions($period, $accountInfo->accountId);
    }

    /**
     * @param Model\Period $period
     * @return Model\Transaction[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getStripeTransactions(Model\Period $period): array
    {
        $stripe = $this->dataSourceFactory->createStripe();

        if (!isset($_ENV['STRIPE_ACCOUNT_NUMBER'])) {
            throw new \UnderflowException(
                "STRIPE_ACCOUNT_NUMBER is not found, please setup one of details above to the .env"
            );
        }

        return $stripe->getTransactions($period);
    }

    /**
     * @param Model\Period $period
     * @return Model\Transaction[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getPayPalTransactions(Model\Period $period): array
    {
        $payPal = $this->dataSourceFactory->createPayPal();

        if (!isset($_ENV['PAYPAL_ACCOUNT_NUMBER'])) {
            throw new \UnderflowException(
                "PAYPAL_ACCOUNT_NUMBER is not found, please setup one of details above to the .env"
            );
        }

        return $payPal->getTransactions($period);
    }

    private function outputToFile(string $xml, string $filename): void
    {
        if (touch($filename) && is_writable($filename)) {
            file_put_contents($filename, $xml);
            echo "Successfully saved to $filename!\n";
        } else {
            throw new \LogicException("{$filename} is not writable, check it's folder permissions.");
        }
    }

    private function outputFileName(Model\Period $period, string $source, string $extension, string $template): string
    {
        return str_replace(
            ['{source}', '{period}', '{extension}'],
            [
                $source,
                $period->fromDate->format('Y-m-d') . '-' . $period->toDate->format('Y-m-d'),
                $extension
            ],
            $template
        );
    }
}