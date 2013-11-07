# webforge-doctrine-compiler

Generate your doctrine entities metadata from a simple json file, including the php code for the entity

## usage

A very basic model (with one entity) could look like this:

```json
{
  "namespace": "ACME\Blog\Entities",

  "entities": [

    {
      "name": "User",
  
      "members": {
        "id": { "type": "DefaultId" },
        "email": { "type": "String" }
      }
    }
}
```

The compiler will create this entity for you:
```php
<?php

namespace ACME\Blog\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User {

  /**
   * @ORM\Id 
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue
   */
  protected $id;

  /** 
   * @ORM\Column(length=200) 
   */
  protected $email;

  public function __construct($email) {
    $this->email = $email;
  }

  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }
  
  /**
   * @param string Email
   * @chainable
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }
}
```

You have the option to use this code and just copy n paste it into your (doctrine)-project. Another option is to put the json model into your project and include the compiler as a dev dependency. If someone changes the model, the entities can be recompiled. You just have to cross the generation-gap here:

`CompiledUser.php`
```php
<?php

namespace ACME\Blog\Entities;

use Doctrine\ORM\Mapping as ORM;

abstract class CompiledUser {

  /**
   * @ORM\Id 
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue
   */
  protected $id;

  /** 
   * @ORM\Column(length=200) 
   */
  protected $email;

  public function __construct($email) {
    $this->email = $email;
  }

  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }
  
  /**
   * @param string Email
   * @chainable
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }
}
```

`User.php`
```php
<?php
namespace ACME\Blog\Entities;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
use Doctrine\ORM\Mapping as ORM;

class User extends CompiledUser {

  protected $somethingUserDefined;

  public function __construct($email, $somethingUserDefined) {
    parent::_construct($email);
    $this->somethingUserDefined = $somethingUserDefined;
  }
}
```

This way your developers should never touch the Compiled** - Entities and always alter the extended classes. This way you're able to easily add new properties to your entities, or change relations between them.