#!/usr/bin/python
# -*- coding: utf-8 -*-

import ConfigParser, os
from datetime import datetime
import imaplib

if __name__ == '__main__':
    print 'Running IMAP Connection Benchmark using Python'
    # Start timer

    start = datetime.now()
    config = ConfigParser.ConfigParser()
    config.readfp(open('config.ini'))

    max_connections = config.get('main', 'max_connections')

    host = config.get('connection', 'host')
    port = config.get('connection', 'port')
    username = config.get('connection', 'username')
    password = config.get('connection', 'password')

    print "Attemping " + str(max_connections) + " connections..."

    connections = []
    connections_made = 0
    connections_failed = 0

    i = 0
    while connections_made < max_connections:
        connections[i] = imaplib.IMAP4(host, port)
        try:
            r, d = connections[i].login(username, password)
            assert r == 'OK', 'login failed'
            connections_made += 1
        except imaplib.IMAP4.error:
            connections_failed += 1

        i += 1


    # End Timer
    end = datetime.now()
    print 'Script completed in ' + str(end - start) + ' seconds'
