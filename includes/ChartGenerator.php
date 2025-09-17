<?php
require_once __DIR__ . '/../config/app.php';

class ChartGenerator {
    
    public static function generateExpenseChart($calculation_data) {
        $chart_data = [
            'labels' => ['Subtotal', 'Tax', 'Service Charge'],
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data' => [
                        $calculation_data['subtotal'],
                        $calculation_data['tax_amount'],
                        $calculation_data['service_charge_amount']
                    ],
                    'backgroundColor' => [
                        'rgba(139, 69, 19, 0.8)',
                        'rgba(160, 82, 45, 0.8)',
                        'rgba(101, 67, 33, 0.8)'
                    ],
                    'borderColor' => [
                        'rgba(139, 69, 19, 1)',
                        'rgba(160, 82, 45, 1)',
                        'rgba(101, 67, 33, 1)'
                    ],
                    'borderWidth' => 2
                ]
            ]
        ];
        
        if ($calculation_data['tips_rate'] > 0) {
            $chart_data['labels'][] = 'Tips';
            $chart_data['datasets'][0]['data'][] = $calculation_data['tips_amount'];
            $chart_data['datasets'][0]['backgroundColor'][] = 'rgba(139, 69, 19, 0.6)';
            $chart_data['datasets'][0]['borderColor'][] = 'rgba(139, 69, 19, 1)';
        }
        
        if ($calculation_data['discount_rate'] > 0) {
            $chart_data['labels'][] = 'Discount';
            $chart_data['datasets'][0]['data'][] = -$calculation_data['discount_amount'];
            $chart_data['datasets'][0]['backgroundColor'][] = 'rgba(0, 128, 0, 0.8)';
            $chart_data['datasets'][0]['borderColor'][] = 'rgba(0, 128, 0, 1)';
        }
        
        return json_encode($chart_data);
    }
    
    public static function generatePerPersonChart($calculation_data) {
        $people = [];
        $amounts = [];
        
        for ($i = 1; $i <= $calculation_data['number_of_people']; $i++) {
            $people[] = 'Person ' . $i;
            $amounts[] = $calculation_data['per_person'];
        }
        
        $chart_data = [
            'labels' => $people,
            'datasets' => [
                [
                    'label' => 'Amount per Person',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(139, 69, 19, 0.8)',
                    'borderColor' => 'rgba(139, 69, 19, 1)',
                    'borderWidth' => 2
                ]
            ]
        ];
        
        return json_encode($chart_data);
    }
}
?>
