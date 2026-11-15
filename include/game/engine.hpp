
#ifndef ATLAS_GAME_ENGINE_HPP
#define ATLAS_GAME_ENGINE_HPP

#include <string>

namespace atlas::game {

  using player_id = unsigned int;
  
  class engine {
  protected:
    bool run;
    player_id serial_pid;
    
  public:
    static constexpr player_id admin_id = 1u;
    
    engine();

    // TODO Convert to message {unsigned int: player id, ?string?: command}
    void process_input(player_id pid, std::string& input);

    bool is_running();
  };
  
}

#endif // ATLAS_GAME_ENGINE_HPP
