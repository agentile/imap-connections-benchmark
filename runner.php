<?php
echo 'Running IMAP Connection Benchmark using PHP' . PHP_EOL;

$time_start = microtime(true);
$config = parse_ini_file('config.ini');
$connections = array();
$connections_made = 0;
$connections_failed = 0;

$memory_usage = array();

echo "Attemping {$config['max_connections']} connections..." . PHP_EOL;

try {
    while (($connections_made + $connections_failed) < $config['max_connections']) {
        $host = "{{$config['host']}:{$config['port']}/imap/ssl}INBOX";
        $mem = memory_get_usage();
        $connection = imap_open($host, $config['username'], $config['password']);
        if ($connection) {
            $connections[] = $connection;
            $connections_made++;
        } else {
            $connections_failed++;
        }

        $memory_usage[] = memory_get_usage() - $mem;
        echo '.';
    }
} catch (Exception $e) {
    $connections_failed++;
}

$time_end = microtime(true);
$time = $time_end - $time_start;

echo PHP_EOL;

$memory_avg = convert(array_sum($memory_usage) / $connections_made);

echo "Total of {$connections_made} IMAP Connections were made with average memory usage of {$memory_avg} per connection!" . PHP_EOL;
echo "Total of {$connections_failed} IMAP Connections failed!" . PHP_EOL;

echo "Script completed in {$time} seconds" . PHP_EOL;

// http://www.php.net/manual/en/function.memory-get-usage.php#96280
function convert($size) {
    $unit = array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).$unit[$i];
}
