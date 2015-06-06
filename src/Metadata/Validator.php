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
        $countPrimaryKeys = 0;

        foreach($tableMetadata['columns'] as $column) {
            if ( ! empty($column['primary'])) {
                $countPrimaryKeys++;
            }
        }

        if ($countPrimaryKeys == 0) {
            throw new DomainException('No primary key defined in class ' . $metadata['class'] . '.');
        } elseif ($countPrimaryKeys > 1) {
            throw new DomainException('No composite primary keys allowed for class ' . $metadata['class'] . '.');
        }
    }

    /**
     * Check if pivot tables of bi-directional relations are identically.
     *
     * @param array $metadataArray
     * @return void
     */
    public function validatePivotTables($metadataArray)
    {
        $pivotTables = [];
        foreach($metadataArray as $metadata) {
            foreach($metadata['relations'] as $relationMetadata) {
                if ( ! empty($relationMetadata['pivotTable'])) {
                    $pivotTables[$metadata['class'].$relationMetadata['relatedClass']] = $relationMetadata;

                    if (isset($pivotTables[$relationMetadata['relatedClass'].$metadata['class']])) {
                        $relation1 = $pivotTables[$relationMetadata['relatedClass'].$metadata['class']];
                        $relation2 = $relationMetadata;

                        $error = null;

                        // check name
                        if ($relation1['pivotTable']['name'] != $relation2['pivotTable']['name']) {
                            $error = 'Different table names (compared '.$relation1['pivotTable']['name'].' with '.$relation2['pivotTable']['name'].').';
                        }

                        // check name
                        if ( ! empty(array_diff_key($relation1['pivotTable']['columns'], $relation2['pivotTable']['columns']))) {
                            $columns1 = implode(', ', array_keys($relation1['pivotTable']['columns']));
                            $columns2 = implode(', ', array_keys($relation2['pivotTable']['columns']));
                            $error = 'Different column names (compared '.$columns1.' with '.$columns2.').';
                        }

                        if ($error) {
                            throw new DomainException('Error synchronizing pivot tables for relations "'.$relation1['name'].'" in "'.$relation2['relatedClass'].'" and "'.$relation2['name'].'" in "'.$relation1['relatedClass'].'": '.$error);
                        }
                    }
                }

            }
        }
    }

}