<?php
echo 'Running IMAP Connection Benchmark using PHP (IMAP Extension)' . PHP_EOL;

$time_start = microtime(true);
$config = parse_ini_file('config.ini');
$connections = array();
$connections_made = 0;
$connections_failed = 0;

$memory_usage = array();
$open_time = array();

echo "Attemping {$config['max_connections']} connections..." . PHP_EOL;

try {
    while (($connections_made + $connections_failed) < $config['max_connections']) {
        $host = "{{$config['host']}:{$config['port']}/imap/ssl}INBOX";
        $mem = memory_get_usage();
        $open_start = microtime(true);
        $connection = imap_open($host, $config['username'], $config['password']);
        if ($connection) {
            $connections[] = $connection;
            $connections_made++;
            $memory_usage[] = memory_get_usage() - $mem;
            $open_time[] = microtime(true) - $open_start;
        } else {
            $connections_failed++;
        }
        echo '.';
    }
} catch (Exception $e) {
    $connections_failed++;
}

$time_end = microtime(true);
$time = $time_end - $time_start;

echo PHP_EOL;

$memory_avg = round((array_sum($memory_usage) / $connections_made) / 1024, 2);
$time_avg = round(array_sum($open_time) / $connections_made, 2) * 1000;

echo "Total of {$connections_made} IMAP Connections were made with average memory usage of {$memory_avg}kb per connection and average of {$time_avg} ms to open a connection!" . PHP_EOL;
echo "Total of {$connections_failed} IMAP Connections failed!" . PHP_EOL;

echo "Script completed in {$time} seconds" . PHP_EOL;

