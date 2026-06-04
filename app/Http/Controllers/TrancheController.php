<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TrancheController extends Controller
{
    public function __construct(private GoogleSheetsService $sheetsService)
    {
    }

    public function index()
    {
        $unusedCount = 0;
        $error = null;

        try {
            $unusedCount = $this->sheetsService->countUnusedPhones();
        } catch (\Exception $e) {
            $error = 'Не удалось подключиться к Google Sheets: ' . $e->getMessage();
        }

        $nextTranche = $this->getNextTranche();

        return view('index', compact('unusedCount', 'nextTranche', 'error'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:50000',
        ]);

        $count = (int) $request->input('count');

        try {
            // 1. Get unused phones
            $unusedPhones = $this->sheetsService->getUnusedPhones();

            if (empty($unusedPhones)) {
                return back()->withErrors(['count' => 'Нет доступных неиспользованных номеров.']);
            }

            // 2. Take only requested count
            $selectedPhones = array_slice($unusedPhones, 0, $count);
            $actualCount = count($selectedPhones);

            // 3. Get templates
            $templates = $this->sheetsService->getTemplates();

            if (empty($templates)) {
                return back()->withErrors(['count' => 'Шаблоны сообщений не найдены в Google Sheets.']);
            }

            // 4. Get current tranche number
            $tranche = $this->getNextTranche();

            // 5. Build data rows: phone | text1 | text2 | tranche
            $rows = [];
            foreach ($selectedPhones as $i => $phoneData) {
                // Cycle through templates if needed
                $template = $templates[$i % count($templates)];

                $rows[] = [
                    'phone'   => $phoneData['phone'],
                    'text1'   => $template['text1'],
                    'text2'   => $template['text2'],
                    'tranche' => $tranche,
                ];
            }

            // 6. Generate XLSX
            $filePath = $this->generateXlsx($rows, $tranche);

            // 7. Mark phones as used in Google Sheets
            $phoneRows = array_column($selectedPhones, 'row');
            $this->sheetsService->markPhonesUsed($phoneRows, $tranche);

            // 8. Mark templates with tranche in Google Sheets
            $this->sheetsService->markTemplatesWithTranche($tranche, $actualCount);

            // 9. Increment tranche counter
            $this->incrementTranche();

            // 10. Stream file download
            $fileName = "tranche_{$tranche}_{$actualCount}_номеров.xlsx";

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->withErrors(['count' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    private function generateXlsx(array $rows, int $tranche): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Транш $tranche");

        // Header row
        $headers = ['Номер телефона', 'Текст 1', 'Текст 2', 'Транш'];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFD1D5DB'],
                ],
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Data rows
        $rowData = [];
        foreach ($rows as $row) {
            $rowData[] = [
                $row['phone'],
                $row['text1'],
                $row['text2'],
                $row['tranche'],
            ];
        }
        $sheet->fromArray($rowData, null, 'A2');

        // Style data rows (alternating)
        $totalRows = count($rows) + 1;
        for ($i = 2; $i <= $totalRows; $i++) {
            $bgColor = ($i % 2 === 0) ? 'FFF9FAFB' : 'FFFFFFFF';
            $sheet->getStyle("A{$i}:D{$i}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFE5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(60);
        $sheet->getColumnDimension('D')->setWidth(12);

        // Enable text wrap for text columns
        $sheet->getStyle("B2:C{$totalRows}")->getAlignment()->setWrapText(true);

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto-filter
        $sheet->setAutoFilter("A1:D1");

        // Save to temp file
        $filePath = storage_path('app/public/tranche_' . $tranche . '_' . time() . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    private function getNextTranche(): int
    {
        $path = storage_path('app/tranche_counter.txt');
        if (!file_exists($path)) {
            $startingTranche = (int) config('google.starting_tranche', 107);
            file_put_contents($path, $startingTranche);
            return $startingTranche;
        }
        return (int) file_get_contents($path);
    }

    private function incrementTranche(): void
    {
        $current = $this->getNextTranche();
        $path = storage_path('app/tranche_counter.txt');
        file_put_contents($path, $current + 1);
    }
}
