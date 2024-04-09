<style>
	tab {
		margin-left: 20px;
	}
</style>

# ABOUT

SMP is a simple MUD engine, using PHP.

# TELNET NOTES

hex => decimal => char

fd06 => 253 006

0d0a => 013 010 => CR LF

# ARCHITECTURE

Overview of the components involved.

![Architecture Diagram](doc/architecture.png "Architecture Diagram")

1. Do not overcomplicate.
2. Can be ported to C/C++.
3. What is the most important thing to concentrate on? Project Goals?

# SMP

Simple MUD in PHP.

## Short Term Goals

1. Basic RPG - no particular setting yet.
2. Transient DB - everything is lost on restart.
3. Multiplayer obviously.
4. Concentrate more on software design.

## Game World

Any character should be playable.<br />
<tab />- Any sentient character is included (animals, mythical creatures, drones, etc).

**Logical hierarchy**

Universe &rarr; World / Planet / Large body &rarr; Continent / Region &rarr; ?Chunk?

**Physical hierarchy**

Only applies to universes?<br />
Different servers/DBs?

## Entities

## Types of Entities

## Network
