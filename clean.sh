#!/usr/bin/env bash

echo "Cleaning build related files..."
rm -Rf -v aclocal.m4 autom4te.cache config.h.in* config.guess config.sub \
   install-sh ltmain.sh configure missing $(find . -iname Makefile.in) \
   build-aux/* depcomp compile autoscan.log configure.scan

echo "Cleaning default build directory..."
rm -Rf -v bin/*

echo "Restoring default build directory..."
mkdir -p bin
