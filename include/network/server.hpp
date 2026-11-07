
#ifndef ATLAS_NETWORK_SERVER_HPP
#define ATLAS_NETWORK_SERVER_HPP

#include <memory>
#include <unordered_set>
#include <chrono>

#include <boost/asio/io_context.hpp>
#include <boost/asio/ip/tcp.hpp>
#include <boost/asio/steady_timer.hpp>

#include "network/tcp_connection.hpp"

using namespace boost::asio;
using namespace boost::asio::ip;

namespace atlas::network {
  
  class server {
  protected:
    static constexpr std::chrono::milliseconds tick_timer_period = std::chrono::milliseconds(5);
    
    io_context io_ctx;
    tcp::acceptor acceptor;
    std::unordered_set<tcp_connection_ptr> conns;
    std::unordered_set<tcp_connection_ptr> conns_closed;
    steady_timer tick_timer;

    void accept(const char* msg);
    void tick();

  public:
    server();
    void run();
    void stop();
  };

}

#endif // ATLAS_NETWORK_SERVER_HPP
