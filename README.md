# Laravel Data Mapper

`**Important:** The first version of the Laravel Data Mapper is actually in development and will be published as soon as possible.`

An easy to use data mapper for Laravel 5 that fits perfectly to the approach of Domain Driven Design (DDD). In general the Laravel Data Mapper is an extension to the Laravel Query Builder. You can build queries by using all of the query builder methods and in addition you can pass plain old PHP objects (popo's) to the builder and also return popo's from the builder.

## Installation

Laravel Data Mapper is distributed as a composer package. So you first have to add the package to your `composer.json` file:

```
"markusjwetzel/laravel-data-mapper": "1.0.*"
```

Then you have to run `composer update` to install the package. Once this is completed, you have to add the service provider to the providers array in `config/app.php`:

```
'Markusjwetzel\LaravelDataMapper\LaravelDataMapperServiceProvider'
```

If you want to use a facade for the entity manager, you can create an alias in the aliases array of `config/app.php`:

```
'EntityManager' => 'Markusjwetzel\LaravelDataMapper\EntityManagerFacade'
```

Run php artisan vendor:publish to publish this package configuration. Afterwards you can edit the file `config/datamapper.php`.

## Usage

### Annotations

We will map all classes to a database table by using annotations. Annotations are doc-comments that you add to a class. The annotations are quite similar to the Doctrine 2 annotations. Here is a simple example of a `User` class:

```php
<?php

use Markusjwetzel\LaravelDataMapper\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;
    
    ...
}
```

For a full documentation on all annotations see the wiki.

### Migrations

Once you have defined the annotations, you can run `php artisan schema:create`. This command will walk through all registered classes and will populate the database based on the defined annotations. You can also run `php artisan schema:drop` or `php artisan schema:update` to drop all database tables or update the database based on the annotations.

You can set a `--generate_migrations` flag on create or update command. If so, this package will generate laravel migration files for all the database tables and save them to the default migrations directory (e. g. `database/migrations`).

### Entity Manager

As already mentioned the Laravel Data Mapper is an extension of the Laravel Query Builder, so you can use all methods of the query builder. You can get an instance of the entity manager by using the `EntityManager` facade or by using method injection:

```php
<?php

use Markusjwetzel/LaravelDataMapper/EntityManager;

class UserRepository {

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }
    
    ...
    
}
```

In addition to define a table for the query with `$em->table('users')`, you can pass an entity class or an entity object to select a table (e. g. `$em->class('Entity\User')` or `$em->object($user)`).

#### Example #1: Get a User object by ID

`$user = $em->class('Entity\User')->where('id',$id)->get();`

#### Example #2: Insert, update and delete a record

`$em->object($user)->insert();`

`$em->object($user)->update();`

`$em->object($user)->delete();`

Hint: Relational objects are not inserted or updated.

#### Example #3: Eager Loading

`$users = $em->class('Entity\User')->with('User.comments')->get();`

You can use the `with()` method the same way as you use it with Eloquent objects. Chained dot notations can be used (e. g. `->with('User.comments.likes')`

#### Example #4: Versioning

If an entity has the `@ORM\Versionable` annotation, you can use the versioning methods:

`$users = $em->class('Entity\User')->where('id',$id)->allVersions();`
`$users = $em->class('Entity\User')->where('id',$id)->getVersion(1);`

Hint: `get()` returns always the latest version.

## Support

Bugs and feature requests are tracked on [GitHub](https://github.com/markusjwetzel/laravel-data-mapper/issues)

## License

This package is released under [the MIT License](LICENSE).
