#!/usr/bin/env bash

if [ ! -f project-config.jam ]; then
    echo "'project-config.jam' not found, abort"
    echo "make sure the 'boost-bootstrap.sh' was ran, before running this script"
    exit 1
fi

CMD="./b2 $@ variant=release link=shared runtime-link=shared threading=multi"

echo "will execute: "
echo "$CMD headers"
echo "$CMD install"

while true; do
    read -p "do you want to compile and install Boost? " yn
    case $yn in
        [Yy]* )
            $CMD headers
            $CMD install
            break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done
