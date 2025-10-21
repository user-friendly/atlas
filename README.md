# ABOUT

SMP is a simple MUD engine, using PHP.

# Program Execution

Starting the game server:

`sh daemon`

Run PHPUnit test suite: 

`sh test`

Run a specific case (exclude `tests/` directory from path):

`sh test Ui/Command/TokenizerTest.php`

Run simple test script (non-PHPUnit):

`sh test Simple\\Fibers`

Enable xDebug for a given test run, pass `xdebug` as the first argument:

`sh test xdebug Ui/Command/TokenizerTest.php`

# TEST NOTES

Run tests multiple times, especially for the ones that use static test
objects.

# GAME LOOP NOTES

Currently in order to achieve (as precise as possible) ticks per second, the main game loops
busy-waits. It is a major hog on CPU but I couldn't find another way (using PHP). It is acceptable
for now, since it won't run non-stop given the active experimental development stage. However, if 
the game server is going to be running on say AWS, this might cause major issues.

Consider implementing throttling when the game is idle, i.e. no players are connected. It will have
an impact on profiling and have to figure out how the simulation steps will be done.

# Refactor

Alpha4 will take a major shift towards decreasing abstractions. This looks over-engineered already. Add complexity when needed.
Of course, it could make it difficult to introduce it back later on, but I'll cross that bridge when it comes to it.

## Textual UI

The Input/Output UI classes can keep track of the network client to user associations.

Currently, the Output UI class aggregates the messages to be sent to individual users. The Game class then resolves the user to network client association and sends the raw input to the network server, identified by the client id.

# TELNET NOTES

hex => decimal => char

fd06 => 253 006

0d0a => 013 010 => CR LF

# DESIGN DOC

See the [DESIGN.md](DESIGN.md "Design Document") for the Vision!
