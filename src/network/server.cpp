
#include <iostream>
#include <memory>
#include <string>
// #include <utility>

#include <boost/system.hpp>
#include <boost/asio/io_context.hpp>
#include <boost/asio/ip/tcp.hpp>
#include <boost/asio/write.hpp>
#include <boost/algorithm/string/trim.hpp>

// Local includes.
#include "network/tcp_connection.hpp"
#include "network/server.hpp"

using namespace std;


using boost::asio::ip::tcp;

using namespace atlas::network;

server::server() :
  io_ctx {8},
  acceptor {io_ctx},
  conns {},
  tick_timer {io_ctx}
{}

void server::accept(const char* msg) {
  acceptor.async_accept([this, msg](const boost::system::error_code ec, tcp::socket peer) {
    if (ec) {
      cerr << "accept error: " << ec.message() << endl;
      return;
    }
    tcp_connection_ptr new_peer {make_shared<tcp_connection>(std::move(peer))};

    cout << "client connected: " << new_peer->remote_address() << endl;
    
    conns.insert(new_peer);
    new_peer->init();
    new_peer->write(string(msg));
    new_peer->write("MUD is under construction, so we'll have to boot you out!\n\n"s);
    new_peer->write_flush();
    accept(msg);
    });
}

void server::tick() {
  tick_timer.expires_after(tick_timer_period);
  tick_timer.async_wait([this](const boost::system::error_code ec){
    if (ec) {
      cerr << "network tick timer error: " << ec.message() << endl;
      return;
    }
    
    if (!conns.empty()) {
      for (const auto& con : conns) {
        if (!con->is_open()) {
          cout << "client disconnected: " << con->remote_address() << endl;
          conns_closed.insert(con);
          continue;
        }
        
        string line {con->read()};
        boost::trim_right(line);
        
        if (line == "quit" || line == "exit") {
          con->close();
          conns_closed.insert(con);
          continue;
        } else if (!line.empty()) {
          line += "\n";
          con->write(line);
        }
        
        con->write_flush();
      }
      
      for (const auto& con_rem : conns_closed) {
        conns.erase(con_rem);
      }
    }
    tick();
    });
}

void server::run() {
  cout << "Starting network server on port 9000." << endl;
  
  const char msg[] {"\nHello!\nWelcome to the Multi-User Dungeon.\n\n"};

  tcp::endpoint endpoint {tcp::v4(), 9000};

  acceptor.open(endpoint.protocol());
  
  boost::system::error_code ec_bind {};
  acceptor.bind(endpoint);
  if (ec_bind) {
    cerr << "bind error: " << ec_bind.message() << endl;
    return;
  }
  acceptor.listen();

  accept(msg);
  
  tick();
  
  io_ctx.run();

  cout << "Stopped network server." << endl;
}

void server::stop() {
  io_ctx.stop();
}
