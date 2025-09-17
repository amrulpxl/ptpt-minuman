<?php
require_once __DIR__ . '/includes/ErrorHandler.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/SessionManager.php';

$sessionManager = SessionManager::getInstance();
$sessionManager->initSession();
$preferences = $sessionManager->getPreferences();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="title"><?php echo APP_NAME; ?></h1>
            <p class="subtitle">Perhitungan Biaya Bersama dengan Pajak dan Service Charge</p>
            <div class="header-actions">
                <button id="settings-btn" class="header-btn">⚙️ Pengaturan</button>
                <button id="history-btn" class="header-btn">📋 Riwayat</button>
            </div>
        </header>

        <main class="main-content">
            <form id="expense-form" class="expense-form">
                <div class="form-section">
                    <h3>Detail Minuman</h3>
                    <div id="items-container"></div>
                    <button type="button" id="add-item-btn" class="add-item-btn">+ Tambah Item</button>
                </div>

                <div class="form-section">
                    <h3>Pengaturan Perhitungan</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tax_rate">Persentase Pajak (%)</label>
                            <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="<?php echo $preferences['default_tax_rate']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="service_charge">Persentase Service Charge (%)</label>
                            <input type="number" id="service_charge" name="service_charge" step="0.01" min="0" max="100" value="<?php echo $preferences['default_service_charge']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="discount_rate">Persentase Diskon (%)</label>
                            <input type="number" id="discount_rate" name="discount_rate" step="0.01" min="0" max="100" value="0">
                        </div>
                        <div class="form-group">
                            <label for="tips_rate">Persentase Tips (%)</label>
                            <input type="number" id="tips_rate" name="tips_rate" step="0.01" min="0" max="100" value="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="number_of_people">Jumlah Orang</label>
                            <input type="number" id="number_of_people" name="number_of_people" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="rounding_mode">Mode Pembulatan</label>
                            <select id="rounding_mode" name="rounding_mode">
                                <?php foreach (ROUNDING_MODES as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $preferences['rounding_mode'] === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" id="calculate-btn" class="calculate-btn">Hitung Patungan</button>
            </form>

            <div id="results-container"></div>
            
            <div id="charts-container" class="charts-container" style="display: none;">
                <div class="chart-section">
                    <h3>Breakdown Biaya</h3>
                    <canvas id="expense-chart"></canvas>
                </div>
                <div class="chart-section">
                    <h3>Pembagian per Orang</h3>
                    <canvas id="per-person-chart"></canvas>
                </div>
            </div>
        </main>

        <div id="settings-panel" class="settings-panel" style="display: none;">
            <h3>Pengaturan</h3>
            <div class="form-group">
                <label for="default_tax_rate">Pajak Default (%)</label>
                <input type="number" id="default_tax_rate" step="0.01" min="0" max="100" value="<?php echo $preferences['default_tax_rate']; ?>">
            </div>
            <div class="form-group">
                <label for="default_service_charge">Service Charge Default (%)</label>
                <input type="number" id="default_service_charge" step="0.01" min="0" max="100" value="<?php echo $preferences['default_service_charge']; ?>">
            </div>
            <div class="form-group">
                <label for="default_currency">Mata Uang Default</label>
                <select id="default_currency">
                    <?php foreach (SUPPORTED_CURRENCIES as $currency): ?>
                        <option value="<?php echo $currency; ?>" <?php echo $preferences['default_currency'] === $currency ? 'selected' : ''; ?>>
                            <?php echo $currency; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="default_rounding_mode">Mode Pembulatan Default</label>
                <select id="default_rounding_mode">
                    <?php foreach (ROUNDING_MODES as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $preferences['rounding_mode'] === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="save-settings-btn" class="save-settings-btn">Simpan Pengaturan</button>
        </div>

        <div id="history-panel" class="history-panel" style="display: none;">
            <h3>Riwayat Perhitungan</h3>
            <div id="history-container"></div>
            <button id="clear-history-btn" class="clear-history-btn">Hapus Semua Riwayat</button>
        </div>

        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> by <?php echo APP_AUTHOR; ?>. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/calculator.js"></script>
</body>
</html>