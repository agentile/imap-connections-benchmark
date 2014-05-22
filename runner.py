#!/usr/bin/python
# -*- coding: utf-8 -*-

import ConfigParser, os, sys, imaplib, time

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
    print 'Running IMAP Connection Benchmark using Python (imaplib)'
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
    connections_made = 0
    connections_failed = 0
    memory = []
    times = []

    while (connections_made + connections_failed) < int(max_connections):
        sys.stdout.write(".")
        sys.stdout.flush()
        open_time = current_milli_time()
        mem = memory_usage()
        mail = imaplib.IMAP4_SSL(host, int(port))
        try:
            r, d = mail.login(username, password)
            memory.append(memory_usage() - mem)
            times.append(current_milli_time() - open_time)
            assert r == 'OK', 'login failed'
            connections.append(mail)
            connections_made += 1
        except imaplib.IMAP4.error:
            connections_failed += 1

    # End Timer
    end = current_milli_time()

    print ""

    avg_memory_usage = "%.1f" % (sum(memory) / connections_made)
    time_per_connection = str(sum(times) / connections_made)
    print "Total of " + str(connections_made) + " IMAP Connections were made with average memory usage of " + avg_memory_usage + "kb per connection and average of " + time_per_connection + " ms to open a connection!"
    print "Total of " + str(connections_failed) + " IMAP Connections failed!"

    print 'Script completed in ' + str(round((end - start) / 1000, 2)) + ' seconds'
