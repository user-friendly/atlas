
#include <iostream>

#include "network/server.hpp"

using namespace std;
using namespace boost;
using namespace boost::asio;
using namespace boost::asio::ip;
using namespace atlas::network;

server::server() :
  io_ctx {8},
  acceptor {io_ctx, tcp::endpoint(tcp::v4(), 9000)}
{}

void server::run() {
  cout << "Starting network server on port 9000." << endl;
  
  io_context io_ctx {};

  const char msg[] {"\nHello!\nWelcome to the Multi-User Dungeon.\n\n"};

  for (;;) {
    tcp::socket socket {io_ctx};
    acceptor.accept(socket);

    system::error_code ignored_error {};
    write(socket, buffer(msg), ignored_error);
  }

  cout << "Stopped network server." << endl;
}
