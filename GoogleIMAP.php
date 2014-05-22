<?php
/**
* Google Mail IMAP IDLE
*
* @author Anthony Gentile <asgentile@gmail.com>
*/
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
        // NO [ALERT] Too many simultaneous connections. (Failure)
        // NO [AUTHENTICATIONFAILED] Invalid credentials (Failure)
        return false;
    }

    public function idle() {
        $this->request("SELECT INBOX");
        $response = $this->getResponse();
        if (isset($response['result']) && strstr($response['result'], 'INBOX selected')) {
            $this->request("IDLE");
            $response = $this->getResponse();
            if (isset($response['other']) && $response['other'] == '+ idling') {
                $this->waitForResponse();
            }
        }
        return false;
    }

    public function waitForResponse() {
        $response = null;
        while (empty($response)) {
            $response = $this->getResponse();
        }
        $this->handleResponse($response);
        $this->idle();
    }

    public function handleResponse($response) {
        if (isset($response['info']) && preg_match('/\* ([\d]+) EXISTS/', $response['info'], $matches)) {
            $this->handleNewMessage((int) $matches[1]);
        }
    }

    public function handleNewMessage($message_id) {
        $message = $this->fetchMessage($message_id);
    }

    public function fetchMessage($message_id) {
        // http://tools.ietf.org/search/rfc3501#section-6.4.5
        // shoot me in the face.
        $this->request("FETCH {$message_id} (X-GM-MSGID)");
        //$this->request("UID FETCH {$message_id} (FLAGS UID RFC822.SIZE INTERNALDATE BODY.PEEK[HEADER] BODY)");
        $response = $this->getResponse();
    }

    public function request($cmd) {
        if (strpos($cmd, 'FETCH') === 0) {
            $command = "{$cmd}\r\n";
        } else {
            $command = "{$this->_id} {$cmd}\r\n";
        }
        //echo $command . PHP_EOL;
        fwrite($this->_conn, $command);
    }

    public function getResponse($limit = 3) {
        if (!$this->_conn) {
            return array();
        }

        $ret = '';
        $arr = array();

        $time_diff = 0;
        $start = time();

        while (!feof($this->_conn) && $time_diff < 3) {
            $char = fgetc($this->_conn);
            if ($ret && $char === false) {
                break;
            }
            $ret .= $char;
            $time_diff = time() - $start;
        }

        $lines = explode("\n", trim($ret));

        foreach ($lines as $line) {
            if (strpos($line, $this->_id) === 0) {
                $arr['result'] = trim($line);
            } else if (strpos($line, '*') === 0) {
                $arr['info'] = trim($line);
            } else {
                if (trim($line)) {
                    $arr['other'] = trim($line);
                }
            }
        }

        //var_dump($arr);
        return $arr;
    }
}