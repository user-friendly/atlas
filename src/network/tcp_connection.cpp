
#include <string>
#include <iostream>
#include <utility>
#include <memory>

#include <boost/system.hpp>
#include <boost/asio/io_context.hpp>
#include <boost/asio/write.hpp>
#include <boost/asio/ip/tcp.hpp>
#include <boost/asio/error.hpp>

#include "network/tcp_connection.hpp"

using namespace boost;
using namespace boost::asio;

using namespace atlas::network;

tcp_connection::tcp_connection(ip::tcp::socket peer) :
  sock {std::move(peer)},
  input {}
{}

void tcp_connection::init() {
  read_async();
}

void tcp_connection::read_async() {
  std::shared_ptr<tcp_connection> self {shared_from_this()};
  sock.async_read_some(buffer(input_buff), [this, self](system::error_code ec, std::size_t n) {
    if (ec == error::eof) {
      return;
    }
    
    if (ec) {
      std::cerr << "socket read error: " << ec.message() << std::endl;
      close();
      return;
    }

    if (n > 0) {

      #ifdef DEBUG
      std::cout << "socket recv: "; // std::string_view(input.data(), n)
      std::cout.write(input_buff.data(), n);
      std::cout << std::endl;
      #endif

      input.push(string(input_buff.data(), 0, n));      
      
    } else {
      std::cerr << "socket read error: nothing to read!" << std::endl;
    }
    
    read_async();
    
    });
}

void tcp_connection::write_async() {
  vector<const_buffer> write_buffs {output.size()};
  
  while (!output.empty()) {
    output_buff.push_back(std::move(output.front()));
    write_buffs.push_back(buffer(output_buff.back()));
    output.pop();
  }
  
  std::shared_ptr<tcp_connection> self {shared_from_this()};
  async_write(sock, write_buffs, [this, self](system::error_code ec, std::size_t n){
    if (ec) {
      std::cerr << "socket write error: " << ec.message() << std::endl;
      close();
      return;
    }
    output_buff.clear();
  });
}

string tcp_connection::read() {
  if (!input.empty()) {
    string out = std::move(input.front());
    input.pop();
    return out;
  }
  
  return string();
}

void tcp_connection::write(string str) {
  output.push(str);
}

void tcp_connection::write_flush() {
  if (output.empty() || !output_buff.empty()) {
    return;
  }

  write_async();
}

void tcp_connection::close() {
  sock.close();
}

bool tcp_connection::is_open() {
  return sock.is_open();
}

string tcp_connection::remote_address() {
  return sock.remote_endpoint().address().to_string();
}
