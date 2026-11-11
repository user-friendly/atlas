/**
 * Network server.
 */

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif

#include <iostream>
#include <memory>
#include <string>
// #include <utility>

#include <boost/system.hpp>
#include <boost/asio/io_context.hpp>
#include <boost/asio/ip/tcp.hpp>
#include <boost/asio/write.hpp>
#include <boost/asio/error.hpp>
#include <boost/algorithm/string/trim.hpp>

// Local includes.
#include "network/tcp_connection.hpp"
#include "network/server.hpp"

using namespace std;

using boost::asio::ip::tcp;

using namespace atlas::network;

server::server() :
  io_ctx {io_ctx_co_hint},
  tick_timer {io_ctx},
  tick_timer_run {true},
  acceptor_address {address_v4::any()},
  acceptor {io_ctx},
  conn_serial_id {0},
  conns {},
  conns_closed {}
{
  // unsigned int const max_threads { thread::hardware_concurrency() };
  // cout << max_threads << " concurrent threads are supported.\n";
}

void server::accept(const char* msg) {
  acceptor.async_accept([this, msg](const boost::system::error_code ec, tcp::socket peer) {
    if (ec == error::operation_aborted) {
      #ifdef DEBUG
      std::cout << "acceptor: operation aborted (closed?)" << endl;
      #endif
      return;
    }
    
    if (ec) {
      cerr << "accept error: " << ec.message() << endl;
      return;
    }
    tcp_connection_ptr new_peer {make_shared<tcp_connection>(std::move(peer))};

    connection_id new_peer_id = conn_serial_id++;
    conns[new_peer_id] = new_peer;

    cout << "client connected [" << new_peer_id << "]: "
         << new_peer->remote_address()
         << endl;
    
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
      if (ec == error::operation_aborted) {
#ifdef DEBUG
        std::cout << "network tick timer: operation aborted (canceled?)" << endl;
#endif
      } else {
        cerr << "network tick timer error: " << ec.message() << endl;
      }
      return;
    }
    
    if (!conns.empty()) {
      for (const auto& pair : conns) {
        const connection_id& con_id = pair.first;
        const tcp_connection_ptr& con = pair.second;
        if (!con->is_open()) {
          cout << "disconnect client [" << con_id << "]: connection closed" << endl;
          conns_closed.push(pair.first);
          continue;
        }
        
        string line {con->read()};
        boost::trim_right(line);
        
        if (line == "quit" || line == "exit") {
          cout << "disconnect client [" << con_id << "]: user quit" << endl;
          con->close();
          conns_closed.push(pair.first);
          continue;
        } else if (!line.empty()) {
          line += "\n";
          con->write(line);
        }
        
        con->write_flush();
      }
      
      for (std::size_t i = 0; i < conns_close_batch && !conns_closed.empty(); i++) {
        conns.erase(conns_closed.front());
        conns_closed.pop();
      }
    }
    if (tick_timer_run) {
      tick();
    }
    });
}

void server::run() {
  const char msg[] {"\nHello!\nWelcome to the Multi-User Dungeon.\n\n"};
  tcp::endpoint endpoint_v4 {acceptor_address, port_default};
  acceptor.open(endpoint_v4.protocol());

  cout << "Starting network server, bind to: ";
  cout << endpoint_v4 << endl;
  
  boost::system::error_code ec_bind {};
  acceptor.bind(endpoint_v4);
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
  post(io_ctx, [this]{
    tick_timer_run = false;

    acceptor.close();
    
    for (const auto& pair : conns) {
      const connection_id& con_id = pair.first;
      const tcp_connection_ptr& con = pair.second;
      cout << "disconnect client [" << con_id << "]" << endl;
      con->close();
    }
    });
}
