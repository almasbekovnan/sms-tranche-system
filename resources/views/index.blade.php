<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Транш Генератор</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 680px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            box-shadow: 0 8px 32px rgba(37, 99, 235, 0.4);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 6px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        /* Cards */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 20px;
        }

        /* Stats row */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .stat-box {
            background: #0f172a;
            border: 1px solid #1e3a5f;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #38bdf8;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .stat-box.tranche .stat-value {
            color: #a78bfa;
        }

        /* Form */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 10px;
        }

        .form-label span {
            color: #64748b;
            font-weight: 400;
            font-size: 13px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 18px;
            font-weight: 600;
            color: #f1f5f9;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            -moz-appearance: textfield;
        }

        .form-input::-webkit-outer-spin-button,
        .form-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .form-input::placeholder {
            color: #475569;
            font-weight: 400;
        }

        .input-hint {
            margin-top: 8px;
            font-size: 12px;
            color: #475569;
        }

        /* Quick select buttons */
        .quick-select {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .quick-btn {
            flex: 1;
            padding: 8px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
        }

        .quick-btn:hover {
            background: #1e3a5f;
            border-color: #2563eb;
            color: #38bdf8;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 24px rgba(37, 99, 235, 0.35);
        }

        .btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-icon {
            font-size: 20px;
        }

        /* Progress indicator */
        .progress-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }

        .progress-overlay.active {
            display: flex;
        }

        .progress-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 36px 48px;
            text-align: center;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid #334155;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .progress-text {
            color: #f1f5f9;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .progress-sub {
            color: #64748b;
            font-size: 13px;
        }

        /* Error alert */
        .alert-error {
            background: #1c0a0a;
            border: 1px solid #7f1d1d;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error::before {
            content: '⚠️';
            font-size: 16px;
            flex-shrink: 0;
        }

        /* Info row */
        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #0f172a;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .info-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            flex-shrink: 0;
        }

        .info-dot.orange {
            background: #f59e0b;
        }

        .info-text {
            font-size: 13px;
            color: #94a3b8;
        }

        .info-text strong {
            color: #e2e8f0;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #475569;
            font-size: 12px;
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
        }

        .footer a:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="header-icon">📨</div>
        <h1>SMS Транш Генератор</h1>
        <p>Автоматическое создание рассылок из Google Sheets</p>
    </div>

    <!-- Error -->
    @if($error)
        <div class="alert-error">{{ $error }}</div>
    @endif

    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <!-- Stats -->
    <div class="card">
        <div class="card-title">Текущее состояние</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">{{ number_format($unusedCount, 0, '.', ' ') }}</div>
                <div class="stat-label">Доступных номеров</div>
            </div>
            <div class="stat-box tranche">
                <div class="stat-value">{{ $nextTranche }}</div>
                <div class="stat-label">Следующий транш</div>
            </div>
        </div>

        <div style="margin-top: 16px;">
            <div class="info-row">
                <div class="info-dot"></div>
                <div class="info-text">Google Sheets подключен — данные загружены в реальном времени</div>
            </div>
            <div class="info-row">
                <div class="info-dot orange"></div>
                <div class="info-text">После генерации номера автоматически помечаются как <strong>использованные</strong></div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="card">
        <div class="card-title">Создать транш</div>

        <form action="{{ route('tranche.generate') }}" method="POST" id="trancheForm">
            @csrf

            <div class="form-group">
                <label class="form-label" for="count">
                    Количество номеров
                    <span>— максимум {{ number_format($unusedCount, 0, '.', ' ') }} доступно</span>
                </label>
                <div class="input-wrapper">
                    <input
                        type="number"
                        id="count"
                        name="count"
                        class="form-input"
                        placeholder="Например: 2000"
                        min="1"
                        max="{{ $unusedCount }}"
                        value="{{ old('count') }}"
                        required
                    >
                </div>

                <div class="quick-select">
                    <button type="button" class="quick-btn" onclick="setCount(500)">500</button>
                    <button type="button" class="quick-btn" onclick="setCount(1000)">1 000</button>
                    <button type="button" class="quick-btn" onclick="setCount(2000)">2 000</button>
                    <button type="button" class="quick-btn" onclick="setCount(5000)">5 000</button>
                </div>

                <div class="input-hint">
                    Будут взяты первые N неиспользованных номеров из Google Sheets
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" @if($unusedCount === 0) disabled @endif>
                <span class="btn-icon">⬇️</span>
                Сгенерировать и скачать XLSX — Транш {{ $nextTranche }}
            </button>
        </form>
    </div>

    <!-- Info about output -->
    <div class="card">
        <div class="card-title">Структура выходного файла</div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr>
                        <th style="padding: 10px 16px; text-align: left; color: #94a3b8; border-bottom: 1px solid #334155; font-weight: 600;">Столбец</th>
                        <th style="padding: 10px 16px; text-align: left; color: #94a3b8; border-bottom: 1px solid #334155; font-weight: 600;">Содержимое</th>
                        <th style="padding: 10px 16px; text-align: left; color: #94a3b8; border-bottom: 1px solid #334155; font-weight: 600;">Источник</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px; color: #38bdf8; font-weight: 600; border-bottom: 1px solid #1e293b;">A</td>
                        <td style="padding: 10px 16px; color: #e2e8f0; border-bottom: 1px solid #1e293b;">Номер телефона</td>
                        <td style="padding: 10px 16px; color: #64748b; border-bottom: 1px solid #1e293b;">Таблица 1, Лист 3, Столбец B</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; color: #38bdf8; font-weight: 600; border-bottom: 1px solid #1e293b;">B</td>
                        <td style="padding: 10px 16px; color: #e2e8f0; border-bottom: 1px solid #1e293b;">Текст 1</td>
                        <td style="padding: 10px 16px; color: #64748b; border-bottom: 1px solid #1e293b;">Таблица 2, Столбец B</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; color: #38bdf8; font-weight: 600; border-bottom: 1px solid #1e293b;">C</td>
                        <td style="padding: 10px 16px; color: #e2e8f0; border-bottom: 1px solid #1e293b;">Текст 2</td>
                        <td style="padding: 10px 16px; color: #64748b; border-bottom: 1px solid #1e293b;">Таблица 2, Столбец C</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; color: #a78bfa; font-weight: 600;">D</td>
                        <td style="padding: 10px 16px; color: #e2e8f0;">Номер транша</td>
                        <td style="padding: 10px 16px; color: #64748b;">Генерируется автоматически</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        SMS Трансплантация Генератор &mdash;
        <a href="https://docs.google.com/spreadsheets/d/14k2qckLk59rA4sbVt86ljmwsbdZRYV8NQAHh2hEX_mE" target="_blank">Таблица с номерами</a>
        &middot;
        <a href="https://docs.google.com/spreadsheets/d/11Hj1kWdI5b0FJFGMoYvayr9PwLjtl3hhBLaBTFKqFYk" target="_blank">Таблица с шаблонами</a>
    </div>
</div>

<!-- Loading overlay -->
<div class="progress-overlay" id="progressOverlay">
    <div class="progress-card">
        <div class="spinner"></div>
        <div class="progress-text">Генерация транша...</div>
        <div class="progress-sub">Загрузка данных из Google Sheets и создание файла</div>
    </div>
</div>

<script>
    function setCount(n) {
        document.getElementById('count').value = n;
        document.getElementById('count').focus();
    }

    document.getElementById('trancheForm').addEventListener('submit', function(e) {
        const count = parseInt(document.getElementById('count').value);
        const max = {{ $unusedCount }};

        if (count > max) {
            e.preventDefault();
            alert(`Доступно только ${max.toLocaleString()} номеров. Введите меньшее число.`);
            return;
        }

        document.getElementById('progressOverlay').classList.add('active');
        document.getElementById('submitBtn').disabled = true;
    });

    // Hide overlay when download starts (browser navigation event)
    window.addEventListener('focus', function() {
        document.getElementById('progressOverlay').classList.remove('active');
        document.getElementById('submitBtn').disabled = false;
    });
</script>

</body>
</html>
