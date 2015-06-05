<?php namespace Wetzel\Datamapper\Metadata;

use DomainException;
use UnexpectedValueException;

use Wetzel\Datamapper\Metadata\Definitions\Table as TableDefinition;

class Validator {

    /**
     * List of valid attribute types.
     *
     * @var array
     */
    public $columnTypes = [
        'bigIncrements', 'bigInteger', 'binary',
        'boolean', 'char', 'date', 'dateTime',
        'decimal', 'float', 'increments',
        'integer', 'longText', 'mediumText',
        'smallInteger', 'string', 'text',
        'time'
    ];

    /**
     * List of valid relation types.
     *
     * @var array
     */
    public $relationTypes = [
        'belongsTo', 'belongsToMany', 'hasMany',
        'hasManyThrough', 'hasOne', 'morphedByMany',
        'morphMany', 'morphOne', 'morphTo',
        'morphToMany'
    ];

    /**
     * Create a new Eloquent query builder instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Validate a column type.
     *
     * @param string $type
     * @return void
     */
    public function validateColumnType($type)
    {
        if ( ! in_array($type, $this->columnTypes)) {
            throw new UnexpectedValueException('Attribute type "'.$type.'" is not supported.');
        }
    }

    /**
     * Validate a relation type.
     *
     * @param string $type
     * @return void
     */
    public function validateRelationType($type)
    {
        if ( ! in_array($type, $this->relationTypes)) {
            throw new UnexpectedValueException('Relation type "'.$type.'" is not supported.');
        }
    }

    /**
     * Validate the number of primary keys from a table.
     *
     * @param \Wetzel\Datamapper\Metadata\Definitions\Table $tableMetadata
     * @return void
     */
    public function validatePrimaryKey(TableDefinition $tableMetadata)
    {
        // check if all tables have exactly one primary key
        $countPrimaryKeys = $this->countPrimaryKeys($tableMetadata['columns']);
        if ($countPrimaryKeys == 0) {
            throw new DomainException('No primary key defined in class ' . $metadata['class'] . '.');
        } elseif ($countPrimaryKeys > 1) {
            throw new DomainException('No composite primary keys allowed for class ' . $metadata['class'] . '.');
        }
    }

    /**
     * Count primary keys in metadata columns.
     *
     * @param array $columns column metadata
     * @return array
     */
    protected function countPrimaryKeys($columns)
    {
        $count = 0;

        foreach($columns as $column) {
            if ( ! empty($column['primary'])) {
                $count++;
            }
        }

        return $count;
    }

}