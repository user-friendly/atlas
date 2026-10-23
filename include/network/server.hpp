
#include <boost/asio.hpp>

using namespace boost::asio;
using namespace boost::asio::ip;

namespace atlas::network {
  
  class server {
  protected:
    io_context io_ctx;
    tcp::acceptor acceptor;

  public:
    server();
    void run();
  
  };

}
