# Laravel Data Mapper
An easy to use data mapper for Laravel 5 that fits perfectly to the approach of Domain Driven Design (DDD). In general Laravel Data Mapper is an extension to the Laravel Query Builder. You can build queries by using all of the query builder methods and in addition you can pass plain old PHP objects (popo's) to the builder and also return popo's from the builder.

## Installation

Laravel Data Mapper is distributed as a composer package. So you first have to add the package to your `composer.json` file:

```
"markusjwetzel/laravel-data-mapper": "1.0.*"
```

Then you have to run `composer update` to install the package. Once this is completed, you have to add the service provider to the providers array in `config/app.php`:

```
'Markusjwetzel\LaravelDataMapper\LaravelDataMapperServiceProvider'
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

### Build a query



## Support

Bugs and feature requests are tracked on [GitHub](https://github.com/markusjwetzel/laravel-data-mapper/issues)

## License

This package is released under [the MIT License](LICENSE).
