<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    private Sheets $sheets;

    public function __construct()
    {
        $client = new Client();
        $client->setApplicationName('SMS Tranche System');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(config('google.credentials_path'));
        $client->setAccessType('offline');

        $this->sheets = new Sheets($client);
    }

    /**
     * Get all phone numbers with their row index and tranche status.
     * Returns: [['row' => 2, 'phone' => '77771234567', 'tranche' => ''], ...]
     */
    public function getPhoneNumbers(): array
    {
        $spreadsheetId = config('google.phones_spreadsheet_id');
        $sheetName = config('google.phones_sheet_name');
        $phoneCol = config('google.phones_column');
        $trancheCol = config('google.phones_tranche_column');

        // Read phones column
        $phoneRange = "{$sheetName}!{$phoneCol}:{$phoneCol}";
        $phoneResponse = $this->sheets->spreadsheets_values->get($spreadsheetId, $phoneRange);
        $phoneValues = $phoneResponse->getValues() ?? [];

        // Read tranche column
        $trancheRange = "{$sheetName}!{$trancheCol}:{$trancheCol}";
        $trancheResponse = $this->sheets->spreadsheets_values->get($spreadsheetId, $trancheRange);
        $trancheValues = $trancheResponse->getValues() ?? [];

        $result = [];
        foreach ($phoneValues as $index => $row) {
            if (empty($row[0])) continue;

            $phone = trim($row[0]);
            if (empty($phone)) continue;

            // Skip header row
            if ($index === 0 && !is_numeric($phone)) continue;

            $tranche = $trancheValues[$index][0] ?? '';

            $result[] = [
                'row'     => $index + 1, // 1-based row number in sheet
                'phone'   => $phone,
                'tranche' => $tranche,
                'used'    => !empty($tranche),
            ];
        }

        return $result;
    }

    /**
     * Get unused phone numbers (tranche column is empty).
     */
    public function getUnusedPhones(): array
    {
        $all = $this->getPhoneNumbers();
        return array_values(array_filter($all, fn($p) => !$p['used']));
    }

    /**
     * Count unused phone numbers.
     */
    public function countUnusedPhones(): int
    {
        return count($this->getUnusedPhones());
    }

    /**
     * Get message templates.
     * Returns: [['row' => 2, 'text1' => '...', 'text2' => '...'], ...]
     */
    public function getTemplates(): array
    {
        $spreadsheetId = config('google.templates_spreadsheet_id');
        $sheetName = config('google.templates_sheet_name');
        $text1Col = config('google.templates_text1_column');
        $text2Col = config('google.templates_text2_column');

        // Read whole range from A to the text2 column
        $range = "{$sheetName}!A:{$text2Col}";
        $response = $this->sheets->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues() ?? [];

        $colIndex1 = $this->columnLetterToIndex($text1Col);
        $colIndex2 = $this->columnLetterToIndex($text2Col);

        $result = [];
        foreach ($values as $index => $row) {
            // Skip header row
            if ($index === 0) continue;

            $text1 = $row[$colIndex1] ?? '';
            $text2 = $row[$colIndex2] ?? '';

            if (empty($text1) && empty($text2)) continue;

            $result[] = [
                'row'   => $index + 1,
                'text1' => $text1,
                'text2' => $text2,
            ];
        }

        return $result;
    }

    /**
     * Mark phone numbers as used with given tranche number.
     * $rows: array of row numbers (1-based) from the phones sheet.
     */
    public function markPhonesUsed(array $rows, int $tranche): void
    {
        $spreadsheetId = config('google.phones_spreadsheet_id');
        $sheetName = config('google.phones_sheet_name');
        $trancheCol = config('google.phones_tranche_column');

        $data = [];
        foreach ($rows as $row) {
            $range = "{$sheetName}!{$trancheCol}{$row}";
            $data[] = new ValueRange([
                'range'  => $range,
                'values' => [[(string) $tranche]],
            ]);
        }

        if (empty($data)) return;

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW',
            'data'             => $data,
        ]);

        $this->sheets->spreadsheets_values->batchUpdate($spreadsheetId, $body);
    }

    /**
     * Write tranche number to the templates spreadsheet right column.
     */
    public function markTemplatesWithTranche(int $tranche, int $count): void
    {
        $spreadsheetId = config('google.templates_spreadsheet_id');
        $sheetName = config('google.templates_sheet_name');
        $trancheCol = config('google.templates_tranche_column');

        // Write tranche to row 2 (after header) of the tranche column
        $range = "{$sheetName}!{$trancheCol}2";
        $body = new ValueRange([
            'range'  => $range,
            'values' => [[(string) $tranche]],
        ]);

        $this->sheets->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
    }

    private function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $result = 0;
        for ($i = 0; $i < strlen($letter); $i++) {
            $result = $result * 26 + (ord($letter[$i]) - ord('A'));
        }
        return $result;
    }
}
