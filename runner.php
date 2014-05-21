<?php
echo 'Running IMAP Connection Benchmark using PHP' . PHP_EOL;

$time_start = microtime(true);
$config = parse_ini_file('config.ini');
$connections = array();
$connections_made = 0;
$connections_failed = 0;

echo "Attemping {$config['max_connections']} connections..." . PHP_EOL;

try {
    while ($connections_made < $config['max_connections']) {
        $host = "{{$config['host']}:{$config['port']}/imap/ssl}INBOX";
        $connections[] = imap_open($host, $config['username'], $config['password']);
        $connections_made++;
        echo '.';
    }
} catch (Exception $e) {
    $connections_failed++;
}

$time_end = microtime(true);
$time = $time_end - $time_start;

echo PHP_EOL;
echo "Total of {$connections_made} IMAP Connections were made!" . PHP_EOL;
echo "Total of {$connections_failed} IMAP Connections failed!" . PHP_EOL;

echo "Script completed in {$time} seconds" . PHP_EOL;
