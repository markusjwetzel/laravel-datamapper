This page documents the `@Relation` annotation. You can use all relationship types and all parameters of these types that are documented by the [Laravel documentation](http://laravel.com/docs/master/eloquent-relationships).

* [General Terms](#general-terms)
* [Types](#types)
  * [hasOne](#hasone)
  * [hasMany](#hasmany)
  * [hasManyThrough](#hasmanythrough)
  * [belongsTo](#belongsto)
  * [belongsToMany](#belongstomany)
* [Polymorphic Types](#polymorphic-types)
  * [morphOne](#morphone)
  * [morphMany](#morphmany)
  * [morphTo](#morphto)
  * [morphToMany](#morphtomany)
  * [morphedByMany](#morphedbymany)

### General Terms

Terms used for relation sides:

* `owning side`: The side of the relation that contains the relation information (e. g. in a 1-to-1 relation the owning side is the side that contains the foreign key).
* `inversed side`: The other side of the relation.

Terms used for entities and keys:

* `related` Something named 'related' belongs to the related entity.
* `local` Everything (entity, column key etc.) that refers to the entity, which is defined by this class, is named local (i. e. local entity or local key).
* `foreign` The term related is used for columns in a table that do not refer to the table's model.

Example: `localForeignKey` refers to a key that is not in the local table (thus it is foreign in another table), but refers to the local entity.

### Types

In comparison to the Eloquent relationship methods every parameter of the `@Relation` annotation is named. This section shows you which parameter is mapped to which name. The first column No. in each table shows you the position of the parameter in the related Eloquent relationship method. All default values for keys or table names are the same as in Eloquent.

##### hasOne

*used for 1-to-1 relations (inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `localForeignKey` | optional
3 | `localKey` | optional

Example for a `User` entity:
```
@Relation(type="hasOne", relatedEntity="Acme\Models\Phone")
```

##### hasMany

*used for 1-to-n relations (inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `localForeignKey` | optional
3 | `localKey` | optional

Example for a `User` entity:
```
@Relation(type="hasMany", relatedEntity="Acme\Models\Comment")
```

##### hasManyThrough

*used for 1-to-n relations (inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `throughEntity` |
3 | `localForeignKey` | optional
4 | `throughForeignKey` | optional

Example for a `Country` entity:
```
@Relation(type="hasManyThrough", relatedEntity="Acme\Models\Post", throughEntity="Acme\Models\User")
```

##### belongsTo

*used for 1-to-1 and 1-to-n relations (owning side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `relatedForeignKey` | optional
3 | `localKey` | optional
4 | `relation` | optional

This relation type auto-creates a column `{relatedName}_id` in the local table.

Example for a `Phone` entity (1-to-1):
```
@Relation(type="belongsTo", relatedEntity="Acme\Models\User")
```

Example for a `Comment` entity (1-to-n):
```
@Relation(type="belongsTo", relatedEntity="Acme\Models\User")
```

##### belongsToMany

*used for n-to-n relations (owning and inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `pivotTable` | optional
3 | `relatedPivotKey` | optional
4 | `localPivotKey` | optional
5 | `relation` | optional
6 | `inverse` | optional

This relation type auto-creates a pivot table with columns `{relatedName}_id` and `{localName}_id`.

Example for a `Role` entity (owning side):
```
@Relation(type="belongsToMany", relatedEntity="Acme\Models\User")
```
Example for a `User` entity (inversed side):
```
@Relation(type="belongsToMany", relatedEntity="Acme\Models\Role", inverse=true)
```

### Polymorphic Types

##### morphOne

*used for 1-to-1 relations (inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `morphName` | optional
3 | `morphType` | optional
4 | `morphId` | optional
5 | `localKey` | optional

##### morphMany

*used for 1-to-n relations (inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `morphName` | optional
3 | `morphType` | optional
4 | `morphId` | optional
5 | `localKey` | optional

##### morphTo

*used for 1-to-1 and 1-to-n relations (owning side)*

No. | Name | Description
--- | --- | ---
1 | `morphName` | optional
2 | `morphType` | optional
3 | `morphId` | optional

This relation type auto-creates the columns `{morphName}_id` and `{morphName}_type` in the local table.

##### morphToMany

*used for n-to-n relations (owning side and inversed side)*

No. | Name | Description
--- | --- | ---
1 | `relatedEntity` |
2 | `morphName` | optional
3 | `pivotTable` | optional
4 | `relatedPivotKey` | optional
5 | `localPivotKey` | optional
6 | `inverse` | optional

This relation type auto-creates a pivot table with columns `{morphName}_id`, `{morphName}_type` and `{localName}_id`.

##### morphedByMany

*used for n-to-n relations (inversed side)*

morphedByMany is just an alias of morphToMany. It has the same parameters except inverse, which is set to true.