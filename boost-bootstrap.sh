#!/usr/bin/env bash

INSTALL_DIR="$(readlink -f $1)"
echo "install dir is $INSTALL_DIR"
    
if [ ! -d "$INSTALL_DIR" ]; then
    echo "install dir not found or not accessible"
    exit 1
fi

# Uncomment the following to explicitly build all libraries.
#WITH_LIBS="atomic,charconv,chrono,cobalt,container,context,contract,coroutine,date_time,exception,fiber,filesystem,graph,graph_parallel,headers,iostreams,json,locale,log,math,mpi,nowide,process,program_options,python,random,regex,serialization,stacktrace,system,test,thread,timer,type_erasure,url,wave"
# Or just leave --with-libraries empty.
WITH_LIBS=""

if [ "$WITH_LIBS" == "" ]; then
    CMD="./bootstrap.sh --prefix=$INSTALL_DIR --with-toolset=clang"
else
    CMD="./bootstrap.sh --prefix=$INSTALL_DIR --with-libraries=$WITH_LIBS -with-toolset=clang"
fi

echo "will execute: "
echo $CMD

while true; do
    read -p "do you want to prepare Boost for building? " yn
    case $yn in
        [Yy]* )
            $CMD
            break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done
