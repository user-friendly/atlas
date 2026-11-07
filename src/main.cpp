

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
  cout << "\n" << PROG_NAME << " v" << PROG_VERSION << "\n" << endl;

  // unsigned int const max_threads { thread::hardware_concurrency() };
  // cout << max_threads << " concurrent threads are supported.\n";
  // this_thread::sleep_for(std::chrono::milliseconds(500));

  thread t1 ([]{
    // try {
      atlas::network::server tcp_server {};
      tcp_server.run();
    // } catch (std::exception& e) {
    //   cerr << "network tpc server error:\n\t\t" << e.what() << endl;
    // }
  });
  
  cout << "Run game loop." << endl;
  
  string input {};
  
  bool run = true;
  while (run) {
    
    cout << "admin> ";
    cin >> input;
    if (input == "quit" || input == "exit" || input == "q" || input == "e") {
      run = false;
    }
    
  }

  cout << "Game loop ended." << endl;

  t1.join();

  cout << "\nBye!" << endl;
  
  return 0;
}
