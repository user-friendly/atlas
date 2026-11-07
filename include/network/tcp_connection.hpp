
#ifndef ATLAS_NETWORK_TCP_CONNECTION_HPP
#define ATLAS_NETWORK_TCP_CONNECTION_HPP

#include <utility>
#include <memory>
#include <array>
#include <queue>
#include <vector>

#include <boost/asio/ip/tcp.hpp>

using namespace std;
using namespace boost::asio;

namespace atlas::network {

  class tcp_connection
    : public std::enable_shared_from_this<tcp_connection> {
  protected:
    static constexpr size_t buffer_size = 1024;
    
    ip::tcp::socket sock;
    
    // Consider double-buffering.
    array<string::value_type, buffer_size> input_buff;
    vector<string> output_buff;
    
    queue<string> input;
    queue<string> output;

    void read_async();
    void write_async();

  public:
    tcp_connection(ip::tcp::socket peer);

    void init();

    // I/O for now will be copy-by-value.
    // Can be optimized later on.

    string read();
    void write(string str);
    void write_flush();
    
    void close();
    bool is_open();

    string remote_address();
    
  };
  
  using tcp_connection_ptr = std::shared_ptr<tcp_connection>;
}

#endif // ATLAS_NETWORK_TCP_CONNECTION_HPP

