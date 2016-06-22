### Timestamps

If an entity has the `@ORM\Timestamps` annotation, `$timestamps` will be set to true in the mapped Eloquent model, so the created at and updated at timestamp will be updated automatically on insert and update.

Note: This plugin requires a `$createdAt` property and a `$updatedAt` property. You can use the `ProAI\Datamapper\Support\Traits\Timestamps` trait for this.

### SoftDeletes

If an entity has the `@ORM\SoftDeletes` annotation, you can use the soft deleting methods from Eloquent, e. g.:

`$users = $em->class('Entity\User')->withTrashed()->all();`

Note: This plugin requires a `$deletedAt` property. You can use the `ProAI\Datamapper\Support\Traits\SoftDeletes` trait for this.

### Versioning

If an entity has the `@ORM\Versionable` annotation and you have added the `@ORM\Versioned` annotation to all versioned properties, you can use the versioning methods of the [Eloquent Versioning](https://github.com/proai/eloquent-versioning) package. So make sure you have installed this package.

By default the query builder returns always the latest version. If you want a specific version or all versions, you can use the following:

`$user = $em->class('Entity\User')->version(2)->find($id);`

`$users = $em->class('Entity\User')->where('id',$id)->allVersions()->get();`

### Presenters

This package can be extended by the Laravel Datamapper Presenter package. Check out the [Laravel Datamapper Presenter](https://github.com/ProAI/laravel-datamapper-presenter) readme for more information.