<?php
require_once __DIR__ . '/../config/app.php';

class ExportManager {
    
    public static function generatePDF($calculation_data) {
        $html = self::generateHTMLForPDF($calculation_data);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="perhitungan_patungan.pdf"');
        
        echo self::htmlToPDF($html);
    }
    
    private static function htmlToPDF($html) {
        $html = str_replace(['<h1>', '</h1>'], ['<h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px;">', '</h1>'], $html);
        $html = str_replace(['<h2>', '</h2>'], ['<h2 style="font-size: 18px; font-weight: bold; margin: 15px 0;">', '</h2>'], $html);
        $html = str_replace(['<table', '</table>'], ['<table style="width: 100%; border-collapse: collapse; margin: 10px 0;"', '</table>'], $html);
        $html = str_replace(['<th>', '</th>'], ['<th style="border: 1px solid #000; padding: 8px; background: #f0f0f0; text-align: left;">', '</th>'], $html);
        $html = str_replace(['<td>', '</td>'], ['<td style="border: 1px solid #000; padding: 8px;">', '</td>'], $html);
        $html = str_replace(['<tr>', '</tr>'], ['<tr>', '</tr>'], $html);
        $html = str_replace(['<p>', '</p>'], ['<p style="margin: 5px 0;">', '</p>'], $html);
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Perhitungan Patungan Minuman</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kalkulator Patungan Minuman</h1>
        <p>Hasil Perhitungan Patungan Minuman</p>
    </div>
    ' . $html . '
    <div class="footer">
        <p>Dihasilkan oleh Kalkulator Patungan Minuman - ' . date('d F Y H:i:s') . '</p>
    </div>
</body>
</html>';
    }
    
    private static function generateHTMLForPDF($data) {
        $html = '<h1>Hasil Perhitungan Patungan Minuman</h1>';
        $html .= '<p><strong>Tanggal:</strong> ' . date('d F Y H:i:s') . '</p>';
        
        $html .= '<h2>Detail Item:</h2>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Nama Item</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr>';
        
        foreach ($data['items'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
            $html .= '<td>' . Calculator::formatCurrency($item['price']) . '</td>';
            $html .= '<td>' . $item['quantity'] . '</td>';
            $html .= '<td>' . Calculator::formatCurrency($subtotal) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        $html .= '<h2>Ringkasan Perhitungan:</h2>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><td>Subtotal:</td><td>' . Calculator::formatCurrency($data['subtotal']) . '</td></tr>';
        
        if ($data['discount_rate'] > 0) {
            $html .= '<tr><td>Diskon (' . $data['discount_rate'] . '%):</td><td>' . Calculator::formatCurrency($data['discount_amount']) . '</td></tr>';
            $html .= '<tr><td>Setelah Diskon:</td><td>' . Calculator::formatCurrency($data['after_discount']) . '</td></tr>';
        }
        
        $html .= '<tr><td>Pajak (' . $data['tax_rate'] . '%):</td><td>' . Calculator::formatCurrency($data['tax_amount']) . '</td></tr>';
        $html .= '<tr><td>Service Charge (' . $data['service_charge_rate'] . '%):</td><td>' . Calculator::formatCurrency($data['service_charge_amount']) . '</td></tr>';
        
        if ($data['tips_rate'] > 0) {
            $html .= '<tr><td>Tips (' . $data['tips_rate'] . '%):</td><td>' . Calculator::formatCurrency($data['tips_amount']) . '</td></tr>';
        }
        
        $html .= '<tr><td><strong>Total:</strong></td><td><strong>' . Calculator::formatCurrency($data['total']) . '</strong></td></tr>';
        $html .= '<tr><td><strong>Per Orang (' . $data['number_of_people'] . ' orang):</strong></td><td><strong>' . Calculator::formatCurrency($data['per_person']) . '</strong></td></tr>';
        $html .= '</table>';
        
        return $html;
    }
    
    public static function generateQRCode($calculation_data) {
        $data = [
            'total' => $calculation_data['total'],
            'per_person' => $calculation_data['per_person'],
            'people' => $calculation_data['number_of_people'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $qr_data = json_encode($data);
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr_data);
        
        return $qr_url;
    }
    
    public static function generateWhatsAppMessage($calculation_data) {
        $message = "🍻 *Hasil Perhitungan Patungan Minuman*\n\n";
        $message .= "💰 Total: " . Calculator::formatCurrency($calculation_data['total']) . "\n";
        $message .= "👥 Per Orang (" . $calculation_data['number_of_people'] . " orang): " . Calculator::formatCurrency($calculation_data['per_person']) . "\n";
        $message .= "📅 Tanggal: " . date('d F Y H:i:s') . "\n\n";
        $message .= "Dihitung menggunakan Kalkulator Patungan Minuman";
        
        return $message;
    }
}
?>
