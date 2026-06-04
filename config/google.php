<?php

return [
    'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', base_path('google-credentials.json')),

    // Spreadsheet 1: Phone numbers
    'phones_spreadsheet_id' => env('PHONES_SPREADSHEET_ID', '14k2qckLk59rA4sbVt86ljmwsbdZRYV8NQAHh2hEX_mE'),
    'phones_sheet_name'     => env('PHONES_SHEET_NAME', 'Лист3'),
    'phones_column'         => env('PHONES_COLUMN', 'B'),
    'phones_tranche_column' => env('PHONES_TRANCHE_COLUMN', 'D'),

    // Spreadsheet 2: Message templates
    'templates_spreadsheet_id' => env('TEMPLATES_SPREADSHEET_ID', '11Hj1kWdI5b0FJFGMoYvayr9PwLjtl3hhBLaBTFKqFYk'),
    'templates_sheet_name'     => env('TEMPLATES_SHEET_NAME', 'Лист1'),
    'templates_text1_column'   => env('TEMPLATES_TEXT1_COLUMN', 'B'),
    'templates_text2_column'   => env('TEMPLATES_TEXT2_COLUMN', 'C'),
    'templates_tranche_column' => env('TEMPLATES_TRANCHE_COLUMN', 'D'),

    'starting_tranche' => env('STARTING_TRANCHE', 107),
];
