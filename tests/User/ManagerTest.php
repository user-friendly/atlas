<?php
/**
 * @file
 * User Manager test file.
 */

namespace Smp\tests\User;

use Smp\tests\Test;
use Smp\User\Manager;

class ManagerTest extends Test {
  
  public function run() : void {
    {
      $this->logger->info("Create a new Manager object.");
      
      $manager = new Manager();
      
      $context = ['a', 'b'];
      $user = $manager->create("tester", "123", $context);
      
      $this->assert($user->getId() === 1, "New user has the correct id.");
      $this->assert($user->getUsername() === "tester", "New user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert($new_context[0] === "a" && $new_context[1] === "b",
        "New user has the correct context.");
    }
    
    {
      $this->logger->info("Create a new Manager object. The previous one should have been deleted.");
      
      $manager = new Manager();
      
      $context = ['a', 'b'];
      $user = $manager->create("tester1", "123", $context);
      
      $this->assert($user->getId() === 1, "New user has the correct id.");
      $this->assert($user->getUsername() === "tester1", "New user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert($new_context[0] === "a" && $new_context[1] === "b",
        "New user has the correct context.");
      
      
      $user = $manager->create("tester2", "123");
      
      $this->assert($user->getId() === 2, "New user has the correct id.");
      $this->assert($user->getUsername() === "tester2", "New user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert(empty($new_context), "New user has the correct empty context.");
      
      $user = NULL;
      
      $user = $manager->get(2);
      $this->assert($user->getId() === 2, "Existing user has the correct id.");
      $this->assert($user->getUsername() === "tester2", "Existing user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert(empty($new_context), "Existing user has the correct empty context.");
      
      $user = $manager->update($user->getId(), "tester3", "456", $context);
      
      $this->assert(\is_object($user), "Updated user.");
      $user = $manager->get(2);
      
      $this->assert($user->getId() === 2, "Updated user has the correct id.");
      $this->assert($user->getUsername() === "tester3", "Updated user has the correct username.");
      
      $new_context = $user->getContext();
      $this->assert($new_context[0] === "a" && $new_context[1] === "b",
        "Updated user has the correct context.");
    }
  }
}
