
#include <mutex>
#include <utility>

#include "messagequeue.hpp"

using namespace std;

using namespace atlas;

messagequeue::messagequeue() :
  mtx {},
  queue {}
{}

void messagequeue::push(const message& msg) {
  lock_guard<mutex> lock_for_queue(mtx);
  queue.push_back(msg);
}

void messagequeue::push(message&& msg) {
  lock_guard<mutex> lock_for_queue(mtx);
  queue.push_back(std::move(msg));
}

bool messagequeue::pop(message& msg) {
  unique_lock<mutex> lock_for_queue(mtx, try_to_lock);
  if (!lock_for_queue || queue.empty()) {
    return false;
  }
  msg = std::move(queue.front());
  queue.pop_front();
  return true;
}

