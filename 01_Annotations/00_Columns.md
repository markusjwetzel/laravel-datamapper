This page documents the `@Column` annotation. An attribute defined with this annotation belongs to a column in the local table. You can use nearly all column types documented by the [Laravel documentation](http://laravel.com/docs/master/migrations#creating-columns).

### Types

Type | Description
--- | ---
`bigIncrements` | 
`bigInteger` | Optional parameters `unsigned` and `autoIncrement`.
`binary` | Optional parameters `length` and `fixed`.
`boolean` | 
`char` | Optional parameter `length`.
`date` | 
`dateTime` | 
`decimal` | Parameters `scale` and `precision`.
`float` | 
`increments` | 
`integer` | Optional parameters `unsigned` and `autoIncrement`.
`longText` | 
`mediumText` | 
`smallInteger` | Optional parameters `unsigned` and `autoIncrement`.
`string` | Optional parameter `length`.
`text` | 
`time` | 

### Examples

Example for a string column:
```
@Column(type="string")
```

Example for an integer auto-increment primary key (use this instead of the not supported `increments` and `bigIncrements` types):
```
@Id
@AutoIncrement
@Column(type="integer")
```