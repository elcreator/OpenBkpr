<?php
declare(strict_types=1);
/**
 * @author Artur Kyryliuk <mail@artur.work>
 */

namespace App\TargetFormat;

use App\Model;
use App\Model\AccountInfo;

class Csv extends AbstractTargetFormat
{
    private string $extension = 'csv';

    public function __construct()
    {
    }

    /**
     * @param Model\Transaction[] $transactions
     * @param AccountInfo $accountInfo
     * @param Model\Period $period
     * @return false|string
     */
    public function generateFromTransactions($transactions, AccountInfo $accountInfo, Model\Period $period): false|string
    {
        // Open a temporary memory stream for writing CSV data
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open temporary memory stream for CSV generation.');
        }

        // Define CSV headers
        $headers = [
            'Date (MM/DD/YYYY)',
            'Payer/Payee Name',
            'Transaction Id',
            'Transaction Type',
            'Amount',
            'Memo',
        ];

        // Write headers to CSV
        if (fputcsv($handle, $headers) === false) {
            fclose($handle);
            throw new \RuntimeException('Failed to write CSV headers.');
        }

        // Iterate over each transaction and write to CSV if within the period
        foreach ($transactions as $transaction) {
            // Check if the transaction date is within the specified period
            if ($transaction->postedAt < $period->fromDate || $transaction->postedAt > $period->toDate) {
                continue; // Skip transactions outside the period
            }

            // Determine Transaction Type based on amount
            $transactionType = $transaction->amount >= 0 ? 'Credit' : 'Debit';

            // Format the date as MM/DD/YYYY
            $formattedDate = $transaction->postedAt->format('m/d/Y');

            // Prepare the CSV row data
            $row = [
                $formattedDate,
                $transaction->counterpartyName,
                $transaction->id,
                $transactionType,
                number_format(abs($transaction->amount), 2, '.', ''),
                $transaction->note ?? '',
            ];

            // Write the row to CSV
            if (fputcsv($handle, $row) === false) {
                fclose($handle);
                throw new \RuntimeException('Failed to write transaction row to CSV.');
            }
        }

        // Rewind the memory stream to the beginning
        rewind($handle);

        // Retrieve the CSV content as a string
        $csvContent = stream_get_contents($handle);

        // Close the memory stream
        fclose($handle);

        return $csvContent;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
