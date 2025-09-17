<?php
require_once __DIR__ . '/../config/app.php';

class Calculator {
    private static $cache = [];
    
    public static function calculateExpense($items, $tax_rate, $service_charge_rate, $discount_rate, $tips_rate, $number_of_people, $rounding_mode) {
        $cacheKey = md5(serialize(func_get_args()));
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $discount_amount = ($subtotal * $discount_rate) / 100;
        $after_discount = $subtotal - $discount_amount;
        
        $tax_amount = ($after_discount * $tax_rate) / 100;
        $service_charge_amount = ($after_discount * $service_charge_rate) / 100;
        $tips_amount = ($after_discount * $tips_rate) / 100;
        
        $total = $after_discount + $tax_amount + $service_charge_amount + $tips_amount;
        $per_person = $total / $number_of_people;
        
        switch ($rounding_mode) {
            case 'up':
                $per_person = ceil($per_person);
                break;
            case 'down':
                $per_person = floor($per_person);
                break;
            default:
                $per_person = round($per_person, 2);
        }
        
        $result = [
            'subtotal' => $subtotal,
            'discount_amount' => $discount_amount,
            'after_discount' => $after_discount,
            'tax_amount' => $tax_amount,
            'service_charge_amount' => $service_charge_amount,
            'tips_amount' => $tips_amount,
            'total' => $total,
            'per_person' => $per_person,
            'items' => $items,
            'tax_rate' => $tax_rate,
            'service_charge_rate' => $service_charge_rate,
            'discount_rate' => $discount_rate,
            'tips_rate' => $tips_rate,
            'number_of_people' => $number_of_people,
            'rounding_mode' => $rounding_mode
        ];
        
        self::$cache[$cacheKey] = $result;
        return $result;
    }
    
    public static function formatCurrency($amount, $currency = DEFAULT_CURRENCY) {
        $symbol = CURRENCY_SYMBOLS[$currency] ?? CURRENCY_SYMBOLS[DEFAULT_CURRENCY];
        
        switch ($currency) {
            case 'USD':
            case 'EUR':
                return $symbol . number_format($amount, 2);
            default:
                return $symbol . ' ' . number_format($amount, 2, ',', '.');
        }
    }
    
    public static function validateInput($data) {
        $errors = [];
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'Items tidak boleh kosong';
            return $errors;
        }
        
        if (count($data['items']) > 50) {
            $errors[] = 'Maksimal 50 item per perhitungan';
            return $errors;
        }
        
        foreach ($data['items'] as $index => $item) {
            if (!is_array($item)) {
                $errors[] = "Format item ke-" . ($index + 1) . " tidak valid";
                continue;
            }
            
            $name = trim($item['name'] ?? '');
            if (empty($name)) {
                $errors[] = "Nama item ke-" . ($index + 1) . " tidak boleh kosong";
            } elseif (strlen($name) > 100) {
                $errors[] = "Nama item ke-" . ($index + 1) . " terlalu panjang (maksimal 100 karakter)";
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-\.]+$/u', $name)) {
                $errors[] = "Nama item ke-" . ($index + 1) . " mengandung karakter yang tidak diizinkan";
            }
            
            $price = filter_var($item['price'] ?? 0, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0 || $price > 999999999) {
                $errors[] = "Harga item ke-" . ($index + 1) . " harus berupa angka positif (maksimal 999,999,999)";
            }
            
            $quantity = filter_var($item['quantity'] ?? 0, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity <= 0 || $quantity > 999) {
                $errors[] = "Jumlah item ke-" . ($index + 1) . " harus berupa angka positif (maksimal 999)";
            }
        }
        
        $number_of_people = filter_var($data['number_of_people'] ?? 0, FILTER_VALIDATE_INT);
        if ($number_of_people === false || $number_of_people <= 0 || $number_of_people > 100) {
            $errors[] = 'Jumlah orang harus berupa angka positif (maksimal 100)';
        }
        
        $tax_rate = filter_var($data['tax_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($tax_rate === false || $tax_rate < 0 || $tax_rate > 100) {
            $errors[] = 'Persentase pajak harus antara 0-100';
        }
        
        $service_charge = filter_var($data['service_charge'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($service_charge === false || $service_charge < 0 || $service_charge > 100) {
            $errors[] = 'Persentase service charge harus antara 0-100';
        }
        
        $discount_rate = filter_var($data['discount_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($discount_rate === false || $discount_rate < 0 || $discount_rate > 100) {
            $errors[] = 'Persentase diskon harus antara 0-100';
        }
        
        $tips_rate = filter_var($data['tips_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($tips_rate === false || $tips_rate < 0 || $tips_rate > 100) {
            $errors[] = 'Persentase tips harus antara 0-100';
        }
        
        $rounding_mode = $data['rounding_mode'] ?? 'up';
        if (!in_array($rounding_mode, ['up', 'down', 'normal'])) {
            $errors[] = 'Mode pembulatan tidak valid';
        }
        
        return $errors;
    }
}
?>
