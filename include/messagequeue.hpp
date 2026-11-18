
#ifndef ATLAS_MESSAGEQUEUE_HPP
#define ATLAS_MESSAGEQUEUE_HPP

#include <mutex>
#include <deque>

#include "message.hpp"

using namespace atlas;

namespace atlas {
  
  class messagequeue {
  protected:
    std::mutex mtx;
    std::deque<message> queue;
  
  public:
    messagequeue();

    void push(const message& msg);
    void push(message&& msg);
    bool pop(message& msg);

    // TODO Maybe? Take everyting in one call.
    //      Use deque.swap(), it's O(1).
    // std::deque<message> drain();
  };

}  

#endif // ATLAS_MESSAGEQUEUE_HPP
