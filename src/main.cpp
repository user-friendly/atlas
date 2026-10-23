

#include <iostream>
#include <string_view>
#include <string>

#include <boost/asio.hpp>
#include <boost/system.hpp>

#include "network/server.hpp"

using namespace std;
using namespace boost;
using namespace boost::asio::ip;

constexpr string_view PROG_NAME {"Atlas Mud Engine"};
constexpr string_view PROG_VERSION {"1.0.0-alpha4"};

int main(int argc, char* argv[]) {
  cout << "\n" << PROG_NAME << " v" << PROG_VERSION << "\n" << endl;

  try {
    atlas::network::server tcp_server {};
    tcp_server.run();
  } catch (std::exception& e) {
    cerr << "Error:\n\t\t" << e.what() << endl;
  }
  
  cout << "Run game loop." << endl;

  bool run = true;
  while (run) {
    run = false;
  }

  cout << "\nBye!" << endl;
  
  return 0;
}
