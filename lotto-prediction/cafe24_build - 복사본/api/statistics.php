<?php
// statistics.php
require_once 'config.php';

$history_file = __DIR__ . '/../data/lotto_history.json';

if (!file_exists($history_file)) {
    echo json_encode([
        'success' => true,
        'data' => [
            'core_numbers' => [],
            'last_week_numbers' => [],
            'exclude_numbers' => [],
            'total_draws' => 0
        ]
    ]);
    exit;
}

$json_content = file_get_contents($history_file);
$history_data = json_decode($json_content, true);

if (!is_array($history_data)) {
    echo json_encode([
        'success' => true,
        'data' => [
            'core_numbers' => [],
            'last_week_numbers' => [],
            'exclude_numbers' => [],
            'total_draws' => 0
        ]
    ]);
    exit;
}

// Calculate stats
$all_numbers = [];
$last_draw = $history_data[0] ?? ['numbers' => []];

foreach ($history_data as $draw) {
    if (isset($draw['winning_numbers']) && is_array($draw['winning_numbers'])) {
        foreach ($draw['winning_numbers'] as $num) {
            $all_numbers[] = $num;
        }
    }
}

$number_counts = array_count_values($all_numbers);
arsort($number_counts);

$core_numbers = array_slice(array_keys($number_counts), 0, 5);
$exclude_numbers = array_slice(array_keys($number_counts), -5);

sort($core_numbers);
sort($exclude_numbers);

echo json_encode([
    'success' => true,
    'data' => [
        'core_numbers' => $core_numbers,
        'last_week_numbers' => $last_draw['winning_numbers'] ?? [],
        'exclude_numbers' => $exclude_numbers,
        'total_draws' => count($history_data)
    ]
]);
?>
