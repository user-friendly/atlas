#!/usr/bin/env bash

echo "Process id: $$";

function sig_handler()
{
    echo "User defined signal 1 handled."
}

trap sig_handler SIGUSR1

while true; do
    sleep 1
done

echo "Process $$ done."
