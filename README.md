# Laravel Data Mapper
An easy to use data mapper for Laravel 5 that fits perfectly to the needs of Domain Driven Design. Generally Laravel Data Mapper is an extension to the Laravel Query Builder. You can build queries by using all of the query builder methods and in addition you can pass plain old PHP objects (popo's) to the builder and also return popo's from the builder.

## Installation

Laravel Data Mapper is distributed as a composer package. So you first have to add the package to your `composer.json` file:

```
"markusjwetzel/laravel-data-mapper": "1.0.*"
```

Then you have to run `composer update` to install the package. Once this is completed, you add the service provider to the providers array of `config/app.php`:

```
'Markusjwetzel\LaravelDataMapper\LaravelDataMapperServiceProvider'
```

Run php artisan vendor:publish to publish this package configuration. Afterwards you can edit the file `config/datamapper.php`.

## Usage

## Support

Bugs and feature request are tracked on [GitHub](https://github.com/markusjwetzel/laravel-data-mapper/issues)

## License

This package is released under [the MIT License](LICENSE).
