# Laravel Datamapper

[![Latest Stable Version](https://poser.pugx.org/proai/laravel-datamapper/v/stable)](https://packagist.org/packages/proai/laravel-datamapper) [![Total Downloads](https://poser.pugx.org/proai/laravel-datamapper/downloads)](https://packagist.org/packages/proai/laravel-datamapper) [![Latest Unstable Version](https://poser.pugx.org/proai/laravel-datamapper/v/unstable)](https://packagist.org/packages/proai/laravel-datamapper) [![License](https://poser.pugx.org/proai/laravel-datamapper/license)](https://packagist.org/packages/proai/laravel-datamapper)

**Important: This is an alpha version. So it might be that not everything works out of the box. You are welcome to report bugs.**

An easy to use data mapper ORM for Laravel 5 that fits perfectly to the approach of Domain Driven Design (DDD). In general the Laravel Data Mapper is an extension to the Laravel Query Builder. You can build queries by using all of the query builder methods and in addition you can pass Plain Old PHP Objects (POPO's) to the builder and also return POPO's from the builder.

## Installation

Laravel Data Mapper is distributed as a composer package. So you first have to add the package to your `composer.json` file:

```
"proai/laravel-datamapper": "~1.0@dev"
```

Then you have to run `composer update` to install the package. Once this is completed, you have to add the service provider to the providers array in `config/app.php`:

```
'ProAI\Datamapper\DatamapperServiceProvider'
```

If you want to use a facade for the entity manager, you can create an alias in the aliases array of `config/app.php`:

```
'EM' => 'ProAI\Datamapper\Support\Facades\EntityManager'
```

Run `php artisan vendor:publish` to publish this package configuration. Afterwards you can edit the file `config/datamapper.php`.

## Usage

### Annotations

We will map all classes to a database table by using annotations. Annotations are doc-comments that you add to a class. The annotations are quite similar to the Doctrine2 annotations. Here is a simple example of a `User` class:

```php
<?php

use ProAI\Datamapper\Support\Entity;
use ProAI\Datamapper\Annotations as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends Entity
{
    /**
     * @ORM\Id
     * @ORM\AutoIncrement
     * @ORM\Column(type="integer")
     */
    protected $id;
    
    /**
     * @ORM\Embedded(class="Acme\Models\Email")
     */
    protected $email;

    /**
     * @ORM\Relation(type="hasMany", foreignEntity="Acme\Models\Comment")
     */
    protected $comments;
}
```

For a full documentation on all annotations see the wiki.

### Database Schema

Once you have defined the annotations, you can run `php artisan schema:create`. This command will scan all registered classes and will create database tables and will generate a mapped Eloquent model for each entity based on the defined annotations.

You can also update an already existing schema with `php artisan schema:update`. Use the `--save-mode` flag to ensure that old tables will not be deleted.

Furthermore you can drop a schema with `php artisan schema:drop`.

### Entity Manager

As already mentioned the Laravel Data Mapper is an extension of the Laravel Query Builder, so you can use all methods of the query builder. You can get an instance of the entity manager by using the `Entity` facade or by using method injection:

```php
<?php

use ProAI\Datamapper\EntityManager;

class UserRepository {

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }
    
    ...
    
}
```

The entity manager selects a table by passing the classname of an entity to the manager (e. g. `$em->entity('Acme\Models\User')`. The `entity` method then returns an object of the modified Laravel Query Builder, so you can chain all query builder methods after it (see examples).

##### Example #1: Get one or many User objects

`$user = $em->entity('Acme\Models\User')->find($id);` (returns a User object)

`$users = $em->entity('Acme\Models\User')->all();` (returns an ArrayCollection of User objects)

##### Example #2: Insert, update and delete a record

`$em->insert($user);`

`$em->update($user);`

`$em->delete($user);`

Hint: Relational objects are not inserted or updated.

##### Example #3: Eager Loading

`$users = $em->class('Entity\User')->with('comments')->get();`

You can use the `with()` method the same way as you use it with Eloquent objects. Chained dot notations can be used (e. g. `->with('comments.likes')`).

### Entity Plugins

##### Timestamps

If an entity has the `@ORM\Timestamps` annotation, `$timestamps` will be set to true in the mapped Eloquent model, so the created at and updated at timestamp will be updated automatically on insert and update.

Note: This plugin requires a `$createdAt` property and a `$updatedAt` property. You can use the `ProAI\Datamapper\Support\Traits\Timestamps` trait for this.

##### SoftDeletes

If an entity has the `@ORM\SoftDeletes` annotation, you can use the soft deleting methods from Eloquent, e. g.:

`$users = $em->class('Entity\User')->withTrashed()->all();`

Note: This plugin requires a `$deletedAt` property. You can use the `ProAI\Datamapper\Support\Traits\SoftDeletes` trait for this.

##### Versioning

If an entity has the `@ORM\Versionable` annotation and you have added the `@ORM\Versioned` annotation to all versioned properties, you can use the versioning methods of the [Eloquent Versioning](https://github.com/proai/eloquent-versioning) package. So make sure you have installed this package.

By default the query builder returns always the latest version. If you want a specific version or all versions, you can use the following:

`$user = $em->class('Entity\User')->version(2)->find($id);`

`$users = $em->class('Entity\User')->where('id',$id)->allVersions()->get();`

### Presenters

This package has a built-in presenters package. Check out the [Presenters](https://github.com/ProAI/laravel-datamapper/wiki/Presenters) page in the wiki for more information.

## Support

Bugs and feature requests are tracked on [GitHub](https://github.com/proai/laravel-datamapper/issues).

## License

This package is released under the [MIT License](LICENSE).
