<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
    private Sheets $sheets;

    // Column D values 1-6 = already used by previous campaigns.
    // Values 7+ (or empty) = available for new tranches.
    private const USED_GROUP_MAX = 6;

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
     * Get all phone numbers with row index and availability.
     * "Used" = Column D has a value of 1–6 (old campaigns) OR >= 107 (our tranches).
     * "Available" = Column D is empty OR has value 7–106 (pre-batched, not yet sent).
     */
    public function getPhoneNumbers(): array
    {
        $spreadsheetId = config('google.phones_spreadsheet_id');
        $sheetName     = config('google.phones_sheet_name');
        $phoneCol      = config('google.phones_column');
        $statusCol     = config('google.phones_tranche_column');

        $phoneResponse  = $this->sheets->spreadsheets_values->get(
            $spreadsheetId, "{$sheetName}!{$phoneCol}:{$phoneCol}"
        );
        $statusResponse = $this->sheets->spreadsheets_values->get(
            $spreadsheetId, "{$sheetName}!{$statusCol}:{$statusCol}"
        );

        $phoneValues  = $phoneResponse->getValues()  ?? [];
        $statusValues = $statusResponse->getValues() ?? [];

        $result = [];
        foreach ($phoneValues as $index => $row) {
            $phone = trim($row[0] ?? '');
            if (empty($phone)) continue;

            // Skip header row (first row, non-numeric)
            if ($index === 0 && !is_numeric($phone)) continue;

            $statusRaw = trim($statusValues[$index][0] ?? '');
            $statusNum = is_numeric($statusRaw) ? (int) $statusRaw : null;

            // Used if: status 1-6 (old campaign) OR >= 107 (already sent by our system)
            $used = ($statusNum !== null) && (
                ($statusNum >= 1 && $statusNum <= self::USED_GROUP_MAX) ||
                ($statusNum >= 107)
            );

            $result[] = [
                'row'    => $index + 1,
                'phone'  => $phone,
                'status' => $statusRaw,
                'used'   => $used,
            ];
        }

        return $result;
    }

    /**
     * Get available (unused) phone numbers.
     */
    public function getUnusedPhones(): array
    {
        return array_values(array_filter($this->getPhoneNumbers(), fn($p) => !$p['used']));
    }

    /**
     * Count available phone numbers.
     */
    public function countUnusedPhones(): int
    {
        return count($this->getUnusedPhones());
    }

    /**
     * Get message templates from the template spreadsheet.
     * Returns: [['row' => 2, 'text1' => '...', 'text2' => '...'], ...]
     */
    public function getTemplates(): array
    {
        $spreadsheetId = config('google.templates_spreadsheet_id');
        $sheetName     = config('google.templates_sheet_name');
        $text1Col      = config('google.templates_text1_column');
        $text2Col      = config('google.templates_text2_column');

        $response = $this->sheets->spreadsheets_values->get(
            $spreadsheetId, "{$sheetName}!A:{$text2Col}"
        );
        $values = $response->getValues() ?? [];

        $colIndex1 = $this->columnLetterToIndex($text1Col);
        $colIndex2 = $this->columnLetterToIndex($text2Col);

        $result = [];
        foreach ($values as $index => $row) {
            if ($index === 0) continue; // skip header

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
     * Mark phone numbers as processed by writing our tranche number to Column D.
     * $rows: 1-based row numbers in the phones sheet.
     */
    public function markPhonesUsed(array $rows, int $tranche): void
    {
        $spreadsheetId = config('google.phones_spreadsheet_id');
        $sheetName     = config('google.phones_sheet_name');
        $statusCol     = config('google.phones_tranche_column');

        $data = [];
        foreach ($rows as $row) {
            $data[] = new ValueRange([
                'range'  => "{$sheetName}!{$statusCol}{$row}",
                'values' => [[(string) $tranche]],
            ]);
        }

        if (empty($data)) return;

        $this->sheets->spreadsheets_values->batchUpdate(
            $spreadsheetId,
            new \Google\Service\Sheets\BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data'             => $data,
            ])
        );
    }

    /**
     * Write our tranche number to Column D of the templates sheet (header row area).
     */
    public function markTemplatesWithTranche(int $tranche): void
    {
        $spreadsheetId = config('google.templates_spreadsheet_id');
        $sheetName     = config('google.templates_sheet_name');
        $trancheCol    = config('google.templates_tranche_column');

        $range = "{$sheetName}!{$trancheCol}2";
        $body  = new ValueRange(['range' => $range, 'values' => [[(string) $tranche]]]);

        $this->sheets->spreadsheets_values->update(
            $spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']
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
