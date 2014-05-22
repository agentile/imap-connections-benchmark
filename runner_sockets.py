#!/usr/bin/python
# -*- coding: utf-8 -*-

import ConfigParser, sys, os, time, socket, uuid

class googleimap:
    def __init__(self, username=None, password=None):
        self.username = username
        self.password = password

    def __enter__(self):
        return self

    def __exit__(self, type, value, traceback):
        if (self.conn):
            self.conn.shutdown()
            self.conn.close()

    def connect(self):
        self.conn = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.uid = uuid.uuid1()
        try:
            self.conn.connect(('imap.gmail.com', 993))
        except socket.error as e:
            print 'something\'s wrong with %s:%d. Exception type is %s' % (address, port, `e`)
        # set to non-blocking
        self.conn.setblocking(0);
        response = self.getResponse()
        print response
        return False;

    def login(self):
        return False

    def request(self, msg):
        totalsent = 0
        while totalsent < 4096:
            sent = self.sock.send(msg[totalsent:])
            if sent == 0:
                raise RuntimeError("socket connection broken")
            totalsent = totalsent + sent

    def getResponse(self):
        #total data partwise in an array
        total_data=[];
        data='';

        timeout = 1

        #beginning time
        begin=time.time()
        while 1:
            #if you got some data, then break after timeout
            if total_data and time.time()-begin > timeout:
                break

            #if you got no data at all, wait a little longer, twice the timeout
            elif time.time()-begin > timeout*2:
                break

            #recv something
            try:
                data = self.conn.recv(4096)
                if data:
                    print data
                    total_data.append(data)
                    #change the beginning time for measurement
                    begin=time.time()
                else:
                    #sleep for sometime to indicate a gap
                    time.sleep(0.1)
            except:
                pass

        #join all parts to make final string
        print ''.join(total_data)

def current_milli_time():
    return int(round(time.time() * 1000))

# http://stackoverflow.com/questions/897941/python-equivalent-of-phps-memory-get-usage
def memory_usage():
    """Memory usage of the current process in kilobytes."""
    status = None
    result = {'peak': 0, 'rss': 0}
    try:
        # This will only work on systems with a /proc file system
        # (like Linux).
        status = open('/proc/self/status')
        for line in status:
            parts = line.split()
            key = parts[0][2:-1].lower()
            if key in result:
                result[key] = int(parts[1])
    finally:
        if status is not None:
            status.close()
    return result['rss']

if __name__ == '__main__':
    print 'Running IMAP Connection Benchmark using Python (sockets)'
    # Start timer

    start = current_milli_time()
    config = ConfigParser.ConfigParser()
    config.readfp(open('config.ini'))

    max_connections = config.get('main', 'max_connections')

    host = config.get('connection', 'host').strip('"')
    port = config.get('connection', 'port').strip('"')
    username = config.get('connection', 'username').strip('"')
    password = config.get('connection', 'password').strip('"')

    print "Attemping " + str(max_connections) + " connections..."

    connections = []
    connections_attempted = 0
    connections_made = 0
    connections_failed = 0
    memory = []
    times = []

    while connections_attempted < int(max_connections):
        open_time = current_milli_time()
        mem = memory_usage()
        imap = googleimap(username, password)
        imap.connect()
        sys.exit()
        try:
            imap = googleimap(username, password)
            imap.connect()
            sys.exit()
            memory.append(memory_usage() - mem)
            times.append(current_milli_time() - open_time)
            assert r == 'OK', 'login failed'
            connections.append(mail)
            connections_made += 1
        except Exception:
            connections_failed += 1
        connections_attempted += 1

    # End Timer
    end = current_milli_time()

    avg_memory_usage = "%.1f" % (sum(memory) / connections_made)
    time_per_connection = str(sum(times) / connections_made)
    print "Total of " + str(connections_made) + " IMAP Connections were made with average memory usage of " + avg_memory_usage + "kb per connection and average of " + time_per_connection + " ms to open a connection!"
    print "Total of " + str(connections_failed) + " IMAP Connections failed!"

    print 'Script completed in ' + str(round((end - start) / 1000, 2)) + ' seconds'
