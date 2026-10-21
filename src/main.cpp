

#include <iostream>
#include <string_view>
#include <string>

#include <boost/random/random_device.hpp>
#include <boost/random/uniform_int_distribution.hpp>

using namespace std;

constexpr string_view PROG_NAME {"Atlas Mud Engine"};
constexpr string_view PROG_VERSION {"1.0.0-alpha4"};

int main(int argc, char* argv[]) {
  cout << "\n" << PROG_NAME << " v" << PROG_VERSION << "\n" << endl;

  
  boost::random::random_device rng;
  boost::random::uniform_int_distribution<> dist(0, 255);
  cout << "Random number: " << dist(rng) << endl;
  
  cout << "Run game loop." << endl;

  bool run = true;
  while (run) {

    run = false;
  }

  cout << "\nBye!" << endl;
  
  return 0;
}

