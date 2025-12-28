#!/usr/bin/env php
<?php

if (!file_exists('bench_results.csv')) {
    echo "Error: bench_results.csv not found.\n";
    echo "Run: phpbench run --report=none --output=csv > bench_results.csv\n";
    exit(1);
}

$csv = array_map('str_getcsv', file('bench_results.csv'));
$headers = array_shift($csv);

$benchmarks = [];
foreach ($csv as $row) {
    $data = array_combine($headers, $row);
    $subject = $data['subject'];
    $time = (float)$data['time_avg'];
    
    if (!isset($benchmarks[$subject])) {
        $benchmarks[$subject] = [];
    }
    $benchmarks[$subject][] = $time;
}

function percentile($arr, $p) {
    sort($arr);
    $index = ($p / 100) * (count($arr) - 1);
    $lower = floor($index);
    $upper = ceil($index);
    $weight = $index - $lower;
    return $arr[$lower] * (1 - $weight) + $arr[$upper] * $weight;
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "                    DataVerify Performance Analysis                            \n";
echo "                          Percentiles (P50/P95/P99)                            \n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

printf("%-42s %10s %10s %10s %10s\n", "Benchmark", "Mean", "P50", "P95", "P99");
echo str_repeat("─", 95) . "\n";

foreach ($benchmarks as $subject => $times) {
    $mean = array_sum($times) / count($times);
    $p50 = percentile($times, 50);
    $p95 = percentile($times, 95);
    $p99 = percentile($times, 99);
    
    printf(
        "%-42s %9.1fμs %9.1fμs %9.1fμs %9.1fμs\n",
        $subject,
        $mean,
        $p50,
        $p95,
        $p99
    );
}

echo str_repeat("─", 95) . "\n";
echo "\nP50 = 50% of requests faster than this (median)\n";
echo "P95 = 95% of requests faster than this\n";
echo "P99 = 99% of requests faster than this (worst case)\n\n";