<?php
// generate.php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);
$num_combinations = isset($data['num_combinations']) ? (int)$data['num_combinations'] : 5;

if ($num_combinations < 1 || $num_combinations > 100) {
    $num_combinations = 5;
}

// Load history data
$history_file = __DIR__ . '/../data/lotto_history.json';
$history_data = [];
if (file_exists($history_file)) {
    $json_content = file_get_contents($history_file);
    $history_data = json_decode($json_content, true);
}

// Calculate statistics from history (simplified for PHP)
$all_numbers = [];
foreach ($history_data as $draw) {
    if (isset($draw['winning_numbers']) && is_array($draw['winning_numbers'])) {
        foreach ($draw['winning_numbers'] as $num) {
            $all_numbers[] = $num;
        }
    }
}

$number_counts = array_count_values($all_numbers);
arsort($number_counts);

$hot_numbers = array_slice(array_keys($number_counts), 0, 10);
$cold_numbers = array_slice(array_keys($number_counts), -10);

// Generate combinations
$results = [];
$total_generated = 0;
$max_attempts = $num_combinations * 10;

for ($i = 0; $i < $max_attempts; $i++) {
    if (count($results) >= $num_combinations) break;
    $total_generated++;

    $numbers = [];
    while (count($numbers) < 6) {
        $num = rand(1, 45);
        if (!in_array($num, $numbers)) {
            $numbers[] = $num;
        }
    }
    sort($numbers);

    // Apply filters (Simplified Logic)
    
    // 1. Sum Filter (100-200)
    $sum = array_sum($numbers);
    if ($sum < 100 || $sum > 200) continue;

    // 2. AC Value (Complexity) - Simplified check: consecutive numbers
    $consecutive = 0;
    for ($j = 0; $j < 5; $j++) {
        if ($numbers[$j+1] == $numbers[$j] + 1) $consecutive++;
    }
    if ($consecutive > 2) continue; // Reject if too many consecutive numbers

    // 3. Hot/Cold Balance (At least 1 hot, not all cold)
    $hot_count = 0;
    $cold_count = 0;
    foreach ($numbers as $num) {
        if (in_array($num, $hot_numbers)) $hot_count++;
        if (in_array($num, $cold_numbers)) $cold_count++;
    }

    if ($hot_count < 1) continue; // Must have at least 1 hot number
    if ($cold_count > 3) continue; // Too many cold numbers

    // Add to results
    $results[] = [
        'numbers' => $numbers,
        'explanation' => "합계: $sum, 핫넘버: $hot_count 개 포함"
    ];
}

echo json_encode([
    'success' => true,
    'data' => [
        'combinations' => $results,
        'statistics' => [
            'total_generated' => $total_generated,
            'after_filtering' => count($results),
            'filter_rate' => $total_generated > 0 ? round((1 - count($results)/$total_generated) * 100, 1) . '%' : '0%'
        ]
    ]
]);
?>
