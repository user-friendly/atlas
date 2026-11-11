/**
 * Program main entry point.
 */

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <iostream>
#include <string>
#include <string_view>
#include <thread>
#include <chrono>

#include "network/server.hpp"

using namespace std;
using namespace boost;
using namespace boost::asio::ip;

constexpr string_view PROG_NAME {"Atlas Mud Engine"};
constexpr string_view PROG_VERSION {"1.0.0-alpha4"};

int main(int argc, char* argv[]) {
  #ifdef DEBUG
  cout << "DEBUG enabled" << endl;
  #endif
  
  cout << PROG_NAME << " v" << PROG_VERSION << endl;

  // Yes, it is crude and we can just export the server itself.
  // But, this should hopefully signal future me that only the
  // stop method is meant to be executed from outside the thread.
  std::function<void()>* tcp_server_stop = nullptr;
  
  thread t1 ([&tcp_server_stop]{
    atlas::network::server tcp_server {};
    
    std::function<void()> fn = [&tcp_server](){
      tcp_server.stop();
    };
    tcp_server_stop = &fn;
    
    tcp_server.run();
  });
  
  cout << "Run game loop." << endl;
  
  string input {};
  
  bool run = true;
  while (run && !cin.eof()) {
    cout << "admin console> ";
    getline(cin, input);
    if (input == "quit" || input == "exit" || input == "q" || input == "e") {
      run = false;
    }
  }
  cout << "Game loop ended." << endl;

  (*tcp_server_stop)();

  t1.join();

  cout << "Bye!" << endl;
  
  return 0;
}
