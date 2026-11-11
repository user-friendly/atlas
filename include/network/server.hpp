
#ifndef ATLAS_NETWORK_SERVER_HPP
#define ATLAS_NETWORK_SERVER_HPP

#include <cstdint>
#include <memory>
#include <map>
#include <queue>
#include <chrono>

#include <boost/asio/io_context.hpp>
#include <boost/asio/ip/tcp.hpp>
#include <boost/asio/ip/basic_endpoint.hpp>
#include <boost/asio/steady_timer.hpp>

#include "network/tcp_connection.hpp"

using namespace boost::asio;
using namespace boost::asio::ip;

namespace atlas::network {
  using connection_id = std::uint32_t;
  
  class server {
  protected:
    // Io context concurrency hint.
    static constexpr int io_ctx_co_hint = 4;
    static constexpr std::chrono::milliseconds tick_timer_period = std::chrono::milliseconds(5);
    static constexpr port_type port_default = 9000;
    
    io_context io_ctx;
    steady_timer tick_timer;
    bool tick_timer_run;
 
    address acceptor_address;
    tcp::acceptor acceptor;

    connection_id conn_serial_id;
    std::map<connection_id, tcp_connection_ptr> conns;
    std::size_t conns_close_batch = 4;
    std::queue<connection_id> conns_closed;
    
    void accept(const char* msg);
    void tick();

  public:
    server();
    void run();
    void stop();
  };

}

#endif // ATLAS_NETWORK_SERVER_HPP
