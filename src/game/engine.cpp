
#include <iostream>

#include "game/engine.hpp"

using namespace atlas::game;

engine::engine() :
  run {true},
  serial_pid {admin_id+1u}
{}

bool engine::is_running() {
  return run;
}

void engine::process_input(player_id pid, std::string& input) {
  if (pid == admin_id) {
    if (input == "quit" || input == "exit" || input == "q" || input == "e") {
      run = false;
    } else {
      std::cerr << "admin command not found" << std::endl;
    }
    return;
  }

  // TODO Process player input.
}
