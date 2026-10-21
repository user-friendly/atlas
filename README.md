# ABOUT

Atlas is a simple MUD engine, written in C++.

# Program Execution

Starting the game server:

TODO How to start the server?

# TEST NOTES

?Place test notes here?

# GAME LOOP NOTES

TODO Address this when converting to C++.

Currently in order to achieve (as precise as possible) ticks per second, the main game loops
busy-waits. It is a major hog on CPU but I couldn't find another way (using PHP). It is acceptable
for now, since it won't run non-stop given the active experimental development stage. However, if 
the game server is going to be running on say AWS, this might cause major issues.

Consider implementing throttling when the game is idle, i.e. no players are connected. It will have
an impact on profiling and have to figure out how the simulation steps will be done.

# Refactor

## Textual UI

The Input/Output UI classes can keep track of the network client to user associations.

Currently, the Output UI class aggregates the messages to be sent to individual users. The Game class then resolves the user to network client association and sends the raw input to the network server, identified by the client id.

# TELNET NOTES

hex => decimal => char

fd06 => 253 006

0d0a => 013 010 => CR LF

# DESIGN DOC

See the [DESIGN.md](DESIGN.md "Design Document") for the Vision!

# Building

Custom libraries are in the `./libs` directory. Include the directory for both the complier and linker.

Configuration example, presuming `/home/dev/atlas` is the project's absolute directory and the build directory is `/home/dev/atlas/bin`:

```shell
$> export ATLAS_LIBS_DIR="/home/dev/atlas/libs"
$> cd /home/dev/atlas/bin
$> /home/dev/atlas/configure --with-boost=$ATLAS_LIBS_DIR \
                                 CXXFLAGS="-I$ATLAS_LIBS_DIR/include" \
                                 CPPFLAGS="-I$ATLAS_LIBS_DIR/include" \
                                 CFLAGS="-I$ATLAS_LIBS_DIR/include"
```

Note that the build directory can be outside the project's directory.

Run `autoreconf -vfi` first and then the `configure.sh` script. Without any arguments it will use the current directory as source and `./bin` as the build directory.

Change to the build directory and execute `make` there.

Custom Boost
------------

Clone the Github repository and make sure all submodules are initialized ([see this link](https://github.com/boostorg/boost/wiki/Getting-Started)). The compilation and installation guide for 1.92.0 can be found [here](http://www.boost.org/doc/libs/1_92_0/more/getting_started/unix-variants.html#prepare-to-use-a-boost-library-binary). Before initializing the submodules, make sure the version you require is checked out (currently, the minimum boost version required is 1.92.0). Another easier way is to simply grab a tar ball from [Boost.org](https://www.boost.org/users/download/).

There are two shell scripts provided that can help with compiling and installation: `boost-bootstrap.sh` and `boost-build.sh`. The first one prepares for compilation and the second one builds and installs the necessary libraries. Note that both scripts will need to be executed within Boost's source root directory and the `boost-bootstrap.sh` requires one argument - the absolute path to Atlas' source directory. The compiler (toolset) currently set is clang. Simply modify the bootstrap script to change it.

```shell
$> cd /home/dev/boost_1_92_0
$> /home/dev/atlas/boost-bootstrap.sh /home/dev/atlas/libs
$> /home/dev/atlas/boost-build.sh
```
The above example should install all necessary Boost files to `/home/dev/atlas/libs/include` and `/home/dev/atlas/libs/lib`. Make sure those folders are created prior to using the build script.

**Never run any of the scripts as root!**

