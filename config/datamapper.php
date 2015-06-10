<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namespace Tables
    |--------------------------------------------------------------------------
    |
    | If this option is set to true, all tablenames will be auto-generated from
    | namespace. For example a class with the namespace 'Acme\User' will have
    | the tablename 'acme_user'.
    |
    */

    'namespace_tables' => true,

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
    | Base Namespace
    |--------------------------------------------------------------------------
    |
    | If a base namespace is defined, only the classes in the sub-namespace
    | will be scanned by the schema commands. Also you only have to name the
    | sub-namespace in annotations (i. e. for the relatedEntity parameter of
    | the @Relation annotation).
    |
    */

    'base_namespace' => 'Examunity/Domain',

];