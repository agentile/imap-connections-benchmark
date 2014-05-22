#!/usr/bin/env node
// -*- coding: utf-8 -*-

var _  = require('underscore');
var Imap = require('imap');
var iniparser = require('iniparser');
var nodefn = require('when/node');
var when = require('when');
var sequence = require('when/sequence');

_.mixin(require('underscore.string').exports());

function imapConnection(options) {
    "use strict";

    var deferred = when.defer();

    process.stdout.write('.');

    var memory = {
        before: process.memoryUsage().rss,
        after: null
    };

    var client = new Imap(options);
    client.once('ready', function () {
        memory.after = process.memoryUsage().rss;
        client.end();
        deferred.resolve({ "connection_made": true, "memory_usage": memory });
    });

    client.once('error', function(err) {
        deferred.resolve({ "connection_made": false });
    });

    client.connect();

    return deferred.promise;
}

function main() {
    "use strict";

    console.log('Running IMAP Connection Benchmark using node.js');

    var start = process.hrtime();

    return nodefn.call(iniparser.parse, './config.ini')
        .then(function(config) {
            var n = parseInt(config.main.max_connections, 10);
            var tasks = _(n).times(function () { return imapConnection; });

            console.log ("Attemping " + n + " connections...");

            var options = {
                host: _(config.connection.host).trim('"'),
                port: _(config.connection.port).trim('"'),
                user: _(config.connection.username).trim('"'),
                password: _(config.connection.password).trim('"'),
                tls: true
            };

            return sequence(tasks, options);
        })
        .then(function (results) {
            var connections_made = _(results).filter(function (item) {
                return item.connection_made;
            });
            var connections_failed = _(results).filter(function (item) {
                return !item.connection_made;
            });
            var memory = _(connections_made).map(function (item) {
                return item.memory_usage.after - item.memory_usage.before;
            });
            var total_memory_usage = _(memory).reduce(function (memo, item) {
                return memo + item;
            }, 0);

            var avg_memory_usage = total_memory_usage / connections_made.length;

            console.log("");
            console.log("Total of " + connections_made.length + " IMAP Connections " +
                        "were made with average memory usage of " +
                        (avg_memory_usage / 1024) + "kb per connection!");
            console.log("Total of " + connections_failed.length + " IMAP Connections failed!");

            var elapsed = process.hrtime(start);
            console.log("Script completed in " + elapsed[0] + " seconds");
            process.exit(0);
        });
}

main().catch(function (error) {
    console.log('ERROR: ' + error);
});


