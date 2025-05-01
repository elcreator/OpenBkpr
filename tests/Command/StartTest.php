<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\Start;
use App\DataSource;
use App\DataSource\Mercury;
use App\DataSource\PayPal;
use App\DataSource\Stripe;
use App\Model\AccountInfo;
use App\Model\Period;
use App\Model\Transaction;
use App\TargetFormat;
use App\TargetFormat\Camt054_1_04;
use App\TargetFormat\Csv;
use App\TargetFormat\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnderflowException;
use DateTimeImmutable;

class StartTest extends TestCase
{
    private Start $command;
    /** @var array{'code': int, 'text': string}[] */
    private array $outErrorCalls = [];
    /** @var string[] */
    private array $echoLineCalls = [];
    private string $originalTimezone;
    private MockObject $mercuryDataSource;
    private MockObject $stripeDataSource;
    private MockObject $payPalDataSource;
    private MockObject $camtFormat;
    private MockObject $csvFormat;
    private MockObject $jsonFormat;
    private MockObject $dataSourceFactory;
    private MockObject $targetFormatFactory;
    private DateTimeImmutable $stubPostedAt;
    private DateTimeImmutable $stubCreatedAt;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        $this->dataSourceFactory = $this->createMock(DataSource\Factory::class);
        $this->targetFormatFactory = $this->createMock(TargetFormat\Factory::class);
        $this->mercuryDataSource = $this->createMock(Mercury::class);
        $this->stripeDataSource = $this->createMock(Stripe::class);
        $this->payPalDataSource = $this->createMock(PayPal::class);
        $this->camtFormat = $this->createMock(Camt054_1_04::class);
        $this->csvFormat = $this->createMock(Csv::class);
        $this->jsonFormat = $this->createMock(Json::class);

        $this->stubCreatedAt = new DateTimeImmutable('2009-12-28T15:00:00+02:00');
        $this->stubPostedAt = new DateTimeImmutable('2009-12-29T15:00:00+02:00');

        $this->command = $this->getMockBuilder(Start::class)
            ->setConstructorArgs([$this->dataSourceFactory, $this->targetFormatFactory])
            ->onlyMethods(['outError', 'echoLine'])
            ->getMock();
        $this->command->expects($this->any())
            ->method('outError')
            ->willReturnCallback(function($text, $exitCode = null) {
                $this->outErrorCalls[] = ['text' => $text, 'exitCode' => $exitCode];
            });
        $this->command->expects($this->any())
            ->method('echoLine')
            ->willReturnCallback(function($text) {
                $this->echoLineCalls[] = $text;
            });

        $this->outErrorCalls = [];
        $this->echoLineCalls = [];
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/minimal');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        if (isset($_ENV['OUTPUT_FILENAME'])) {
            $filename = str_replace(
                ['{source}', '{period}', '{extension}'],
                ['test', '2023-01-01-2023-01-31', 'test-text'],
                $_ENV['OUTPUT_FILENAME']
            );
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        $_ENV = [];
        $this->outErrorCalls = [];
        $this->echoLineCalls = [];
    }

    public function testRun_missingCompanyName(): void
    {
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/minimal-no-company');
        $this->command->run();
        $this->assertCount(1, $this->outErrorCalls);
        $this->assertCount(1, $this->echoLineCalls);
        $this->assertEquals("COMPANY_NAME is not defined in .env, please setup",
            $this->echoLineCalls[0]);
    }

    public function testRun_missingSources(): void
    {
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/minimal-no-sources');
        $this->command->run();
        $this->assertCount(1, $this->outErrorCalls);
        $this->assertCount(1, $this->echoLineCalls);
        $this->assertEquals("SOURCES is not defined in .env, please setup",
            $this->echoLineCalls[0]);
    }

    public function testRun_invalidSource(): void
    {
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/minimal-no-sources');
        $_ENV['SOURCES'] = 'INVALID';
        $_ENV['OUTPUT_FORMATS'] = 'CSV';
        $this->command->run();
        $this->assertCount(1, $this->outErrorCalls);
        $this->assertEquals('INVALID is not a valid choice for SOURCES, skipping...',
            $this->outErrorCalls[0]['text']);
    }

    public function testRun_missingOutputFormats(): void
    {
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/empty');
        $_ENV['SOURCES'] = 'Stripe';
        $_ENV['COMPANY_NAME'] = 'Acme';
        $this->command->run();
        $this->assertCount(1, $this->outErrorCalls);
        $this->assertMatchesRegularExpression('/OUTPUT_FORMATS is not defined in .env, set it to any of these/',
            $this->outErrorCalls[0]['text']);
    }

    public function testRun_invalidOutputFormat(): void
    {
        $this->command->setEnv(dirname(__DIR__) . '/assets/env/normal');
        $_ENV['OUTPUT_FORMATS'] = 'INVALID';

        $this->stripeDataSource = $this->createMock(Stripe::class);
        $this->dataSourceFactory->expects($this->any())
            ->method('createStripe')
            ->willReturn($this->stripeDataSource);

        $this->command->run();
        $this->assertCount(2, $this->outErrorCalls);
        $this->assertMatchesRegularExpression('/INVALID is not a valid choice for OUTPUT_FORMATS, skipping.../',
            $this->outErrorCalls[0]['text']);
    }

    public function testRun_mercurySuccess(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'Mercury';
        $_ENV['OUTPUT_FORMATS'] = 'CSV';
        $_ENV['MERCURY_TOKEN'] = 'test_mercury_token';
        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_mercury_account_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_mercury_account_number';
        $_ENV['MERCURY_FROM_DATE'] = '2009-11-28';
        $_ENV['MERCURY_TO_DATE'] = '2010-01-28';

        $transactions = $this->getStubTransactions();
        $period = $this->getStubPeriod();
        $accountInfo = $this->getStubAccountInfo('test_mercury_account_id', 'test_mercury_account_number',
            'Test Company', 'USD');

        $this->dataSourceFactory->expects($this->once())
            ->method('createMercury')
            ->willReturn($this->mercuryDataSource);

        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->csvFormat);

        $this->mercuryDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                }),
                'test_mercury_account_id'
            )
            ->willReturn($transactions);

        $this->csvFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->with($transactions, $accountInfo, $period)
            ->willReturn('test_csv_output');

        $this->command->run();
        $this->expectOutputString('test_csv_output');
    }

    public function testRun_stripeSuccess(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'Stripe';
        $_ENV['OUTPUT_FORMATS'] = 'JSON';
        $_ENV['STRIPE_TOKEN'] = 'test_stripe_token';
        $_ENV['STRIPE_ACCOUNT_NUMBER'] = 'test_stripe_account_number';
        $_ENV['STRIPE_CURRENCY'] = 'EUR';
        $_ENV['STRIPE_FROM_DATE'] = '2009-11-28';
        $_ENV['STRIPE_TO_DATE'] = '2010-01-28';

        $period = $this->getStubPeriod();
        $accountInfo = $this->getStubAccountInfo('', 'test_stripe_account_number', 'Test Company', 'EUR');
        $transactions = $this->getStubTransactions();

        $this->dataSourceFactory->expects($this->once())
            ->method('createStripe')
            ->willReturn($this->stripeDataSource);

        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonFormat);

        $this->stripeDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                })
            )
            ->willReturn($transactions);

        $this->jsonFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->with(
                $this->equalTo($transactions),
                $this->equalTo($accountInfo),
                $this->equalTo($period)
            )
            ->willReturn('{"test":"json"}');

        $this->command->run();
        $this->expectOutputString('{"test":"json"}');
    }

    public function testRun_payPalSuccess(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'PayPal';
        $_ENV['OUTPUT_FORMATS'] = 'CAMT54';
        $_ENV['PAYPAL_TOKEN'] = 'test_paypal_token';
        $_ENV['PAYPAL_ACCOUNT_NUMBER'] = 'test_paypal_account_number';
        $_ENV['PAYPAL_CURRENCY'] = 'GBP';
        $_ENV['PAYPAL_FROM_DATE'] = '2009-11-28';
        $_ENV['PAYPAL_TO_DATE'] = '2010-01-28';

        $period = $this->getStubPeriod();
        $accountInfo = $this->getStubAccountInfo('', 'test_paypal_account_number',
            'Test Company', 'GBP');
        $transactions = $this->getStubTransactions();

        $this->dataSourceFactory->expects($this->once())
            ->method('createPayPal')
            ->willReturn($this->payPalDataSource);

        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->camtFormat);

        $this->payPalDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                })
            )
            ->willReturn($transactions);

        $this->camtFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->with($transactions, $accountInfo, $period)
            ->willReturn('<camt></camt>');

        $this->command->run();
        $this->expectOutputString('<camt></camt>');
    }

    public function testRun_mercuryNoTransactions(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'Mercury';
        $_ENV['OUTPUT_FORMATS'] = 'CSV';
        $_ENV['MERCURY_TOKEN'] = 'test_mercury_token';
        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_mercury_account_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_mercury_account_number';
        $_ENV['FROM_DATE'] = '2009-11-28';
        $_ENV['TO_DATE'] = '2010-01-28';

        $period = $this->getStubPeriod();
        $accountInfo = new AccountInfo('test_mercury_account_id', 'test_mercury_account_number', 'Test Company', 'USD');

        $this->dataSourceFactory->expects($this->once())
            ->method('createMercury')
            ->willReturn($this->mercuryDataSource);

        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->csvFormat);

        $this->mercuryDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                }),
                'test_mercury_account_id'
            )
            ->willReturn([]);

        $this->csvFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->with([], $accountInfo, $period)
            ->willReturn(false);

        $this->command->run();

        $this->assertCount(1, $this->outErrorCalls);
        $this->assertEquals('Nothing was generated for Mercury!', $this->outErrorCalls[0]['text']);
    }

    public function testRun_stripeNoTransactions(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'Stripe';
        $_ENV['OUTPUT_FORMATS'] = 'JSON';
        $_ENV['STRIPE_TOKEN'] = 'test_stripe_token';
        $_ENV['STRIPE_ACCOUNT_NUMBER'] = 'test_stripe_account_number';
        $_ENV['STRIPE_CURRENCY'] = 'EUR';

        $this->stripeDataSource = $this->createMock(Stripe::class);
        $this->dataSourceFactory->expects($this->once())
            ->method('createStripe')
            ->willReturn($this->stripeDataSource);

        $this->jsonFormat = $this->createMock(Json::class);
        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->with('JSON')
            ->willReturn($this->jsonFormat);

        $this->stripeDataSource->expects($this->once())
            ->method('getTransactions')
            ->willReturn([]);

        $this->jsonFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->willReturn(false);

        $this->command->run();

        $this->assertCount(1, $this->outErrorCalls);
        $this->assertEquals('Nothing was generated for Stripe!', $this->outErrorCalls[0]['text']);
    }

    public function testRun_payPalNoTransactions(): void
    {
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'PayPal';
        $_ENV['OUTPUT_FORMATS'] = 'CAMT54';
        $_ENV['PAYPAL_TOKEN'] = 'test_paypal_token';
        $_ENV['PAYPAL_ACCOUNT_NUMBER'] = 'test_paypal_account_number';
        $_ENV['PAYPAL_CURRENCY'] = 'GBP';

        $this->dataSourceFactory->expects($this->once())
            ->method('createPayPal')
            ->willReturn($this->payPalDataSource);

        $this->targetFormatFactory->expects($this->once())
            ->method('create')
            ->with('CAMT54')
            ->willReturn($this->camtFormat);

        $this->payPalDataSource->expects($this->once())
            ->method('getTransactions')
            ->willReturn([]);

        $this->camtFormat->expects($this->once())
            ->method('generateFromTransactions')
            ->willReturn(false);

        $this->command->run();

        $this->assertCount(1, $this->outErrorCalls);
        $this->assertEquals('Nothing was generated for PayPal!', $this->outErrorCalls[0]['text']);
    }

    public function testRun_outputToFile(): void
    {
        $tempDir = sys_get_temp_dir();
        $testDir = $tempDir . '/openbkpr_test_' . uniqid();
        if (!is_dir($testDir)) {
            mkdir($testDir, 0777, true);
        }

        $_ENV = [];
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['SOURCES'] = 'Mercury';
        $_ENV['OUTPUT_FORMATS'] = 'CSV';
        $_ENV['MERCURY_TOKEN'] = 'test_mercury_token';
        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_mercury_account_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_mercury_account_number';
        $_ENV['FROM_DATE'] = '2023-01-01';
        $_ENV['TO_DATE'] = '2023-01-31';
        $_ENV['OUTPUT_FILENAME'] = $testDir . '/test_{source}_{period}.{extension}';

        $expectedFilename = str_replace(
            ['{source}', '{period}', '{extension}'],
            ['Mercury', '2023-01-01-2023-01-31', 'csv'],
            $_ENV['OUTPUT_FILENAME']
        );

        $dataSourceFactory = $this->createMock(DataSource\Factory::class);
        $targetFormatFactory = $this->createMock(TargetFormat\Factory::class);
        $mercuryDataSource = $this->createMock(Mercury::class);
        $csvFormat = $this->createMock(Csv::class);

        $dataSourceFactory->method('createMercury')->willReturn($mercuryDataSource);
        $targetFormatFactory->method('create')->willReturn($csvFormat);
        $mercuryDataSource->method('getTransactions')->willReturn([
            new Transaction('test', 123, new DateTimeImmutable(), new DateTimeImmutable(), 'Test', '')
        ]);
        $csvFormat->method('generateFromTransactions')->willReturn('test_csv_output');
        $csvFormat->method('getExtension')->willReturn('csv');

        $command = new Start($dataSourceFactory, $targetFormatFactory);
        $command->setEnv(dirname(__DIR__) . '/assets/env/minimal');
        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString("Successfully saved to", $output);
        $this->assertFileExists($expectedFilename);
        $this->assertStringEqualsFile($expectedFilename, 'test_csv_output');

        if (file_exists($expectedFilename)) {
            unlink($expectedFilename);
        }
        rmdir($testDir);
    }

    public function testGetPeriod_sourceOverride(): void
    {
        $_ENV['MERCURY_FROM_DATE'] = '-3 months';
        $_ENV['FROM_DATE'] = '-2 months';
        $_ENV['MERCURY_TO_DATE'] = '-1 month';
        $_ENV['TO_DATE'] = 'now';


        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getPeriod');
//

        $period = $method->invoke($this->command, 'MERCURY');

        $this->assertEquals(
            (new DateTimeImmutable('-3 months'))->format('Y-m-d H:i:s'),
            $period->fromDate->format('Y-m-d H:i:s')
        );

        $this->assertEquals(
            (new DateTimeImmutable('-1 month'))->format('Y-m-d H:i:s'),
            $period->toDate->format('Y-m-d H:i:s')
        );
    }

    public function testGetPeriod_defaultValues(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getPeriod');
        $period = $method->invoke($this->command, 'MERCURY');

        $this->assertEquals(
            (new DateTimeImmutable('-2 months'))->format('Y-m-d H:i:s'),
            $period->fromDate->format('Y-m-d H:i:s')
        );

        $this->assertEquals(
            (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            $period->toDate->format('Y-m-d H:i:s')
        );
    }

    public function testGetPeriod_globalValues(): void
    {
        $_ENV['FROM_DATE'] = '-1 month';
        $_ENV['TO_DATE'] = '-1 week';

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getPeriod');


        $period = $method->invoke($this->command, 'MERCURY');

        $this->assertEquals(
            (new DateTimeImmutable('-1 month'))->format('Y-m-d H:i:s'),
            $period->fromDate->format('Y-m-d H:i:s')
        );

        $this->assertEquals(
            (new DateTimeImmutable('-1 week'))->format('Y-m-d H:i:s'),
            $period->toDate->format('Y-m-d H:i:s')
        );
    }

    public function testGetMercuryAccountInfo(): void
    {
        $command = new Start();

        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_number';
        $_ENV['COMPANY_NAME'] = 'Test Company';
        $_ENV['MERCURY_ACCOUNT_CURRENCY'] = 'EUR';

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getMercuryAccountInfo');


        $accountInfo = $method->invoke($command);

        $this->assertEquals('test_id', $accountInfo->accountId);
        $this->assertEquals('test_number', $accountInfo->accountNumber);
        $this->assertEquals('Test Company', $accountInfo->ownerName);
        $this->assertEquals('EUR', $accountInfo->currency);
    }

    public function testGetMercuryAccountInfo_defaultCurrency(): void
    {
        $command = $this->getMockBuilder(Start::class)
            ->setConstructorArgs([$this->dataSourceFactory, $this->targetFormatFactory])
            ->onlyMethods(['outError'])
            ->getMock();
        $command->expects($this->any())
            ->method('outError')
            ->willReturnCallback(function($text, $exitCode = null) {
                $this->outErrorCalls[] = ['text' => $text, 'exitCode' => $exitCode];
            });

        $command->setEnv(dirname(__DIR__) . '/assets/env/minimal');

        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__) . '/assets/env/minimal');
        $dotenv->load();

        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_number';
        $_ENV['COMPANY_NAME'] = 'Test Company';

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('getMercuryAccountInfo');


        $accountInfo = $method->invoke($command);

        $this->assertEquals('test_id', $accountInfo->accountId);
        $this->assertEquals('test_number', $accountInfo->accountNumber);
        $this->assertEquals('USD', $accountInfo->currency);
    }

    public function testGetMercuryTransactions_noAccountInfo(): void
    {
        $_ENV['MERCURY_TOKEN'] = 'test_mercury_token';

        $this->dataSourceFactory->expects($this->once())
            ->method('createMercury')
            ->willReturn($this->mercuryDataSource);

        $this->mercuryDataSource->expects($this->once())
            ->method('listAccounts')
            ->willReturn([
                (object)[
                    'name' => 'Test Account',
                    'id' => 'test_account_id',
                    'accountNumber' => 'test_account_number',
                    'legalBusinessName' => 'Test Business'
                ]
            ]);

        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessageMatches(
            '/MERCURY_ACCOUNT_ID or MERCURY_ACCOUNT_NUMBER is not found, please setup one of details above to the .env/'
        );

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getMercuryTransactions');

        $method->invoke(
            $this->command,
            new Period(new DateTimeImmutable(), new DateTimeImmutable()),
            new AccountInfo('', '', '', '')
        );

        $this->expectOutputString(
            "#MERCURY_ACCOUNT_NAME=\"Test Account\"\nMERCURY_ACCOUNT_ID=test_account_id\nMERCURY_ACCOUNT_NUMBER=test_account_number\nCOMPANY_NAME=\"Test Business\"\n\n"
        );
    }

    public function testGetMercuryTransactions_success(): void
    {
        $_ENV['MERCURY_TOKEN'] = 'test_mercury_token';
        $_ENV['MERCURY_ACCOUNT_ID'] = 'test_account_id';
        $_ENV['MERCURY_ACCOUNT_NUMBER'] = 'test_account_number';
        $period = new Period(new DateTimeImmutable('-2 months'), new DateTimeImmutable('now'));
        $accountInfo = new AccountInfo('test_account_id', 'test_account_number', 'Test Company', 'USD');

        $transactions = $this->getStubTransactions();

        $this->dataSourceFactory->expects($this->once())
            ->method('createMercury')
            ->willReturn($this->mercuryDataSource);

        $this->mercuryDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                }),
                'test_account_id'
            )
            ->willReturn($transactions);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getMercuryTransactions');


        $result = $method->invoke($this->command, $period, $accountInfo);
        $this->assertEquals($transactions, $result);
    }

    public function testGetStripeTransactions_noAccountNumber(): void
    {
        $_ENV['STRIPE_TOKEN'] = 'test_stripe_token';
        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessageMatches(
            '/STRIPE_ACCOUNT_NUMBER is not found, please setup one of details above to the .env/'
        );
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getStripeTransactions');

        $method->invoke($this->command, new Period(new DateTimeImmutable(), new DateTimeImmutable()));
    }

    public function testGetStripeTransactions_success(): void
    {
        $_ENV['STRIPE_TOKEN'] = 'test_stripe_token';
        $_ENV['STRIPE_ACCOUNT_NUMBER'] = 'test_account_number';
        $period = new Period(new DateTimeImmutable('-2 months'), new DateTimeImmutable('now'));

        $transactions = $this->getStubTransactions();

        $this->dataSourceFactory->expects($this->once())
            ->method('createStripe')
            ->willReturn($this->stripeDataSource);

        $this->stripeDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                })
            )
            ->willReturn($transactions);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getStripeTransactions');


        $result = $method->invoke($this->command, $period);
        $this->assertEquals($transactions, $result);
    }

    public function testGetPayPalTransactions_noAccountNumber(): void
    {
        $_ENV['PAYPAL_TOKEN'] = 'test_paypal_token';
        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessageMatches(
            '/PAYPAL_ACCOUNT_NUMBER is not found, please setup one of details above to the .env/'
        );
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getPayPalTransactions');

        $method->invoke($this->command, new Period(new DateTimeImmutable(), new DateTimeImmutable()));
    }

    public function testGetPayPalTransactions_success(): void
    {
        $_ENV['PAYPAL_TOKEN'] = 'test_paypal_token';
        $_ENV['PAYPAL_ACCOUNT_NUMBER'] = 'test_account_number';
        $period = new Period(new DateTimeImmutable('-2 months'), new DateTimeImmutable('now'));

        $transactions = $this->getStubTransactions();

        $this->dataSourceFactory->expects($this->once())
            ->method('createPayPal')
            ->willReturn($this->payPalDataSource);

        $this->payPalDataSource->expects($this->once())
            ->method('getTransactions')
            ->with(
                $this->callback(function (Period $argPeriod) use ($period): bool {
                    return $argPeriod->fromDate->format('Y-m-d') === $period->fromDate->format('Y-m-d')
                        && $argPeriod->toDate->format('Y-m-d') === $period->toDate->format('Y-m-d');
                })
            )
            ->willReturn($transactions);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getPayPalTransactions');


        $result = $method->invoke($this->command, $period);
        $this->assertEquals($transactions, $result);
    }

    public function testOutputFileName(): void
    {
        $period = new Period(new DateTimeImmutable('2023-01-01'), new DateTimeImmutable('2023-01-31'));

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('outputFileName');

        $result = $method->invoke(
            $this->command,
            $period,
            'test_source',
            'test_extension',
            'output_{source}_{period}.{extension}'
        );
        $this->assertEquals('output_test_source_2023-01-01-2023-01-31.test_extension', $result);
    }

    private function getStubPeriod(): Period
    {
        return new Period(
            new DateTimeImmutable('2009-11-28', new \DateTimeZone('Europe/Berlin')),
            new DateTimeImmutable('2010-01-28', new \DateTimeZone('Europe/Berlin'))
        );
    }

    private function getStubAccountInfo(string $id = 'test-acc-id', string $number = '123456',
        string $owner = 'test owner', string $currency = 'XXX'): AccountInfo
    {
        return new AccountInfo($id, $number, $owner, $currency);
    }

    /**
     * @return Transaction[]
     */
    private function getStubTransactions(): array
    {
        return [
            new Transaction(
                'test',
                123,
                $this->stubCreatedAt,
                $this->stubPostedAt,
                'Test',
                'test note'
            )
        ];
    }
}
