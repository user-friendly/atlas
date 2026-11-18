
#ifndef ATLAS_MESSAGE_HPP
#define ATLAS_MESSAGE_HPP

#include <string>

#include "game/engine.hpp"

struct message {
  atlas::game::player_id pid;
  std::string data;
};

#endif // ATLAS_MESSAGE_HPP
