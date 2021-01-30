# ABOUT

PHP process control experimentation.

# TODO

* Refactor class property names. Get rid of the underscore.
* Add configurations, via a Symfony Config component.
* Figure out the STREAM_CLIENT_PERSISTENT issue. See the Client class socket
  connection code.

# Notes

* ~Using UNIX sockets for IPC?~ - Yes! And we get net sockets too.
* Symfony's Serializer might help with the actual message body.
* Byte operations might be tricky. Be careful.