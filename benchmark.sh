#!/bin/bash

function relax {
    echo "wait a few seconds to avoid the next test hitting rate limits from the previous one ..."
    printf "[                ]\r["
    for VARIABLE in 0 1 2 3 4 5 6 7 8 9 a b c d e f
    do
        sleep 2s
        echo -n "="
    done
    echo ""
}

time php runner.php
relax
time python runner.py
relax
time node runner.js
