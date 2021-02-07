# ABOUT

A PHP application for parallel work execution.

# TODO

* What will this application do exactly? Something like serverless, but... Decentralized?
    * A network of nodes (graph).
    * Each node can accept work and delegate some to nearby/other nodes.
* Implement simple app protocol for binary data exchange
    * https://en.wikipedia.org/wiki/Type-length-value
    * https://en.wikipedia.org/wiki/Event-driven_finite-state_machine
    * https://en.wikipedia.org/wiki/Protocol_Buffers
* Lock UNIX socket?! Multiple servers can use the same socket.
* Refactor class property names. Get rid of the underscore.
* Add configurations, via a Symfony Config component.
* ~Figure out the STREAM_CLIENT_PERSISTENT issue. See the Client class socket connection code.~
    * Really? Forgot to break from the retry loop... That was one stupid bug.

# Notes

* ~Using UNIX sockets for IPC?~ - Yes! And we get net sockets too.
* Symfony's Serializer might help with the actual message body.
* Byte operations might be tricky. Be careful.
* Streams vs Sockets extension?
