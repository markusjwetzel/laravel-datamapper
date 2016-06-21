There are two ways to create entities and value objects with Laravel Datamapper:

### 1. Extending abstract classes

_This is recommended, because by extending the abstract classes we don't have to use PHP's ReflectionClass. PHP's ReflectionClass can slow down performance by 5-7%._

#### Example

```php
<?php namespace Acme;

use Wetzel\Datamapper\Annotations as ORM;
use Wetzel\Datamapper\Support\Entity;

/**
 * @ORM\Entity
 */
class User extends Entity {

    /**
     * @ORM\Id
     * @ORM\Attribute(type="integer")
     */
    protected $id;

    /**
     * @ORM\Attribute(type="string")
     */
    protected $name;

    /**
     * @ORM\Embedded(class="Acme\Email")
     */
    protected $email;

}
```

```php
<?php namespace Acme;

use Wetzel\Datamapper\Annotations as ORM;
use Wetzel\Datamapper\Support\ValueObject;

/**
 * @ORM\ValueObject
 */
class Email extends ValueObject {

    /**
     * @ORM\Attribute(type="string")
     */
    protected $email;

}
```

### 2. Using Plain Old PHP Objects

#### Example

```php
<?php namespace Acme;

use Wetzel\Datamapper\Annotations as ORM;

/**
 * @ORM\Entity
 */
class User {

    /**
     * @ORM\Id
     * @ORM\Attribute(type="integer")
     */
    private $id;

    /**
     * @ORM\Attribute(type="string")
     */
    private $name;

    /**
     * @ORM\Embedded(class="Acme\Email")
     */
    private $email;

}
```

```php
<?php namespace Acme;

use Wetzel\Datamapper\Annotations as ORM;

/**
 * @ORM\ValueObject
 */
class Email {

    /**
     * @ORM\Attribute(type="string")
     */
    private $email;

}
```