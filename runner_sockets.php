<?php
echo 'Running IMAP Connection Benchmark using PHP (Sockets)' . PHP_EOL;

$time_start = microtime(true);
$config = parse_ini_file('config.ini');
$connections = array();
$connections_made = 0;
$connections_failed = 0;

$memory_usage = array();
$open_time = array();


echo "Attemping {$config['max_connections']} connections..." . PHP_EOL;

try {
    while ($connections_made < $config['max_connections']) {
        $mem = memory_get_usage();
        $open_start = microtime(true);
        $imap = new GoogleIMAP($config['username'], $config['password']);
        if ($imap->connect() && $imap->login()) {
            $connections[] = $imap;
            $memory_usage[] = memory_get_usage() - $mem;
            $open_time[] = microtime(true) - $open_start;
            $connections_made++;
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

class GoogleIMAP {

    protected $_username = null;
    protected $_password = null;
    protected $_id = null;
    protected $_conn = null;

    public function __construct($username, $password) {
        $this->_username = $username;
        $this->_password = $password;
    }

    public function __destruct() {
        if ($this->_conn) {
            fclose($this->_conn);
        }
    }

    public function connect() {
        $this->_id = uniqid();
        $this->_conn = stream_socket_client("ssl://imap.gmail.com:993", $errno, $errstr, 5);
        // set to non-blocking
        stream_set_blocking($this->_conn, 0);
        $response = $this->getResponse();
        if (isset($response['info']) && strstr($response['info'], 'OK Gimap ready')) {
            return true;
        }
        return false;
    }

    public function login() {
        // outh "AUTHENTICATE XOAUTH {$loginstring\}";
        $this->request("LOGIN {$this->_username} {$this->_password}");
        $response = $this->getResponse();
        if (isset($response['result']) && strstr($response['result'], '(Success)')) {
            return true;
        }
        return false;
    }

    public function request($cmd) {
        $command = "{$this->_id} {$cmd}\r\n";
        fwrite($this->_conn, $command);
    }

    public function getResponse() {
        if (!$this->_conn) {
            return array();
        }

        $ret = '';
        $arr = array();

        while (!feof($this->_conn)) {
            $char = fgetc($this->_conn);
            if ($ret && $char === false) {
                break;
            }
            $ret .= $char;
        }

        $lines = explode("\n", trim($ret));

        foreach ($lines as $line) {
            if (strpos($line, $this->_id) === 0) {
                $arr['result'] = trim($line);
            } else if (strpos($line, '*') === 0) {
                $arr['info'] = trim($line);
            } else {
                $arr['other'] = trim($line);
            }
        }

        return $arr;
    }
}

