# webforge-doctrine-compiler

[![Build Status](https://travis-ci.org/webforge-labs/webforge-doctrine-compiler.png)](https://travis-ci.org/webforge-labs/webforge-doctrine-compiler)  
[![Coverage Status](https://coveralls.io/repos/webforge-labs/webforge-doctrine-compiler/badge.png?branch=master)](https://coveralls.io/r/webforge-labs/webforge-doctrine-compiler?branch=master)  
[![Latest Stable Version](https://poser.pugx.org/webforge/doctrine-compiler/version.png)](https://packagist.org/packages/webforge/doctrine-compiler)

Generate your doctrine entities metadata from a simple json file, including the php code for the entity

## usage

A very basic model (with one entity) could look like this:

```json
{
  "namespace": "ACME\\Blog\\Entities",

  "entities": [

    {
      "name": "User",
  
      "properties": {
        "id": { "type": "DefaultId" },
        "email": { "type": "String" }
      }
    }
  ]
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

## Installation

The best way to use the doctrine compiler is to install it as a development tool on your machine.

```
composer global require webforge/doctrine-compiler
```

I recommend to put the global composer bin directory (`~/.composer/vendor/bin` or `%APPDATA%\composer\vendor\bin`) in your PATH.

## Running

You can then compile your entities with the binary installed by composer. You have to provide the location of your json model file and the PSR-1 target-directory for your Entities.

```
~/.composer/vendor/bin/webforge-doctrine-compiler orm:compile etc/doctrine/model.json path/to/my/package/src/php
```

This will create the entities within a PSR-1 named directory in src/php

# LICENSE

The MIT License (MIT)

Copyright (c) 2015 webforge

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.