<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namespace Tablenames
    |--------------------------------------------------------------------------
    |
    | If this option is set to true, all tablenames will be auto-generated from
    | namespace. For example a class with the namespace 'Acme\User' will have
    | the tablename 'acme_user'.
    |
    */

    'namespace_tablenames' => true,

    /*
    |--------------------------------------------------------------------------
    | Morph Class Abbreviations
    |--------------------------------------------------------------------------
    |
    | If this option is enabled, all morph classnames will be converted to a
    | short lower case abbreviation. For example 'Acme\User' will be shortened
    | to 'user'.
    |
    */

    'morphclass_abbreviations' => true,

    /*
    |--------------------------------------------------------------------------
    | Models Namespace
    |--------------------------------------------------------------------------
    |
    | If a models namespace is defined, only the entities and value objects in
    | the sub-namespace will be scanned by the schema commands. Also you only
    | have to name the sub-namespace in annotations (i. e. for the
    | relatedEntity parameter of the @Relation annotation).
    |
    */

    'models_namespace' => '',

    /*
    |--------------------------------------------------------------------------
    | Auto Scan
    |--------------------------------------------------------------------------
    |
    | Automatically scan entity classes and update database on page load. This
    | Option is useful in development mode.
    |
    */

    'auto_scan' => env('APP_AUTO_SCAN', false),

];