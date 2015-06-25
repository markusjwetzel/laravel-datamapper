<?php

namespace ProAI\Datamapper\Metadata;

use Exception;
use DomainException;
use UnexpectedValueException;
use InvalidArgumentException;
use ProAI\Datamapper\Metadata\Definitions\Entity as EntityDefinition;

class EntityValidator
{
    /**
     * List of valid attribute types.
     *
     * @var array
     */
    public $columnTypes = [
        'binary', 'boolean', 'char',
        'date', 'dateTime', 'decimal',
        'float', 'increments', 'integer',
        'longText', 'mediumText', 'smallInteger',
        'string', 'text', 'time'
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
     * Check if class exists.
     *
     * @param string $class
     * @param array $classAnnotations
     * @return void
     */
    public function validateEmbeddedClass($class, $classAnnotations)
    {
        $check = false;

        foreach ($classAnnotations as $annotation) {
            if ($annotation instanceof \ProAI\Datamapper\Annotations\Embeddable) {
                $check = true;
            }
        }

        if (! $check) {
            throw new InvalidArgumentException('Embedded class '.$class.' has no @Embeddable annotation.');
        }
    }

    /**
     * Check if class exists.
     *
     * @param string $class
     * @param string $definedClass
     * @return boolean
     */
    public function validateClass($class, $definedClass)
    {
        try {
            $class = get_real_entity($class);
        } catch (Exception $e) {
            throw new Exception('Class "'.$class.'" (defined in class "'.$definedClass.'") does not exist.');
        }

        return true;
    }

    /**
     * Check if name is not snake case.
     *
     * @param string $name
     * @param string $definedClass
     * @return boolean
     */
    public function validateName($name, $definedClass)
    {
        if ($name != camel_case($name)) {
            throw new Exception('Name "'.$name.'" (defined in class "'.$definedClass.'") is not a camel case name.');
        }

        return true;
    }

    /**
     * Validate a column type.
     *
     * @param string $type
     * @return void
     */
    public function validateColumnType($type)
    {
        if (! in_array($type, $this->columnTypes)) {
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
        if (! in_array($type, $this->relationTypes)) {
            throw new UnexpectedValueException('Relation type "'.$type.'" is not supported.');
        }
    }

    /**
     * Validate the number of primary keys.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Entity $entityMetadata
     * @return void
     */
    public function validatePrimaryKey(EntityDefinition $entityMetadata)
    {
        // check if all tables have exactly one primary key
        $countPrimaryKeys = 0;

        foreach ($entityMetadata['table']['columns'] as $column) {
            if (! empty($column['primary'])) {
                $countPrimaryKeys++;
            }
        }

        if ($countPrimaryKeys == 0) {
            throw new DomainException('No primary key defined in class ' . $entityMetadata['class'] . '.');
        } elseif ($countPrimaryKeys > 1) {
            throw new DomainException('No composite primary keys allowed for class ' . $entityMetadata['class'] . '.');
        }
    }

    /**
     * Validate the timestamps columns.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Entity $entityMetadata
     * @return void
     */
    public function validateTimestamps(EntityDefinition $entityMetadata)
    {
        $columnNames = [];

        // get column names
        foreach ($entityMetadata['table']['columns'] as $column) {
            $columnNames[] = $column['name'];
        }

        // get version column names
        if (! empty($entityMetadata['versionTable'])) {
            foreach ($entityMetadata['versionTable']['columns'] as $column) {
                $columnNames[] = $column['name'];
            }
        }

        if (! in_array('created_at', $columnNames) || ! in_array('updated_at', $columnNames)) {
            throw new DomainException('@Timestamps annotation defined in class ' . $entityMetadata['class'] . ' requires a $createdAt and an $updatedAt column property.');
        }
    }

    /**
     * Validate the softdeletes column.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Entity $entityMetadata
     * @return void
     */
    public function validateSoftDeletes(EntityDefinition $entityMetadata)
    {
        $columnNames = [];

        // get column names
        foreach ($entityMetadata['table']['columns'] as $column) {
            $columnNames[] = $column['name'];
        }

        // get version column names
        if (! empty($entityMetadata['versionTable'])) {
            foreach ($entityMetadata['versionTable']['columns'] as $column) {
                $columnNames[] = $column['name'];
            }
        }

        if (! in_array('deleted_at', $columnNames)) {
            throw new DomainException('@SoftDeletes annotation defined in class ' . $entityMetadata['class'] . ' requires a $deletedAt column property.');
        }
    }

    /**
     * Validate the version table.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Entity $entityMetadata
     * @return void
     */
    public function validateVersionTable(EntityDefinition $entityMetadata)
    {
        $columnNames = [];
        $countPrimaryKeys = 0;
        $versionPrimaryKey = false;

        // get column names
        foreach ($entityMetadata['table']['columns'] as $column) {
            $columnNames[] = $column['name'];
        }

        if (! in_array('latest_version', $columnNames)) {
            throw new DomainException('@Versionable annotation defined in class ' . $entityMetadata['class'] . ' requires a $latestVersion column property.');
        }

        $columnNames = [];

        // get version column names
        foreach ($entityMetadata['versionTable']['columns'] as $column) {
            $columnNames[] = $column['name'];
            if (! empty($column['primary'])) {
                $countPrimaryKeys++;
            }
            if (! empty($column['primary']) && $column['name'] == 'version') {
                $versionPrimaryKey = true;
            }
        }

        if (! in_array('version', $columnNames) || ! $versionPrimaryKey) {
            throw new DomainException('@Versionable annotation defined in class ' . $entityMetadata['class'] . ' requires a $version property column, which is a primary key.');
        }
        if ($countPrimaryKeys > 2) {
            throw new DomainException('No more than 2 primary keys are allowed for version table in class ' . $entityMetadata['class'] . '.');
        }
    }

    /**
     * Check if pivot tables of bi-directional relations are identically.
     *
     * @param array $metadata
     * @return void
     */
    public function validatePivotTables($metadata)
    {
        $pivotTables = [];
        foreach ($metadata as $entityMetadata) {
            foreach ($entityMetadata['relations'] as $relationMetadata) {
                if (! empty($relationMetadata['pivotTable'])) {
                    $pivotTables[$entityMetadata['class'].$relationMetadata['relatedEntity']] = $relationMetadata;

                    if (isset($pivotTables[$relationMetadata['relatedEntity'].$entityMetadata['class']])) {
                        $relation1 = $pivotTables[$relationMetadata['relatedEntity'].$entityMetadata['class']];
                        $relation2 = $relationMetadata;

                        $error = null;

                        // check name
                        if ($relation1['pivotTable']['name'] != $relation2['pivotTable']['name']) {
                            $error = 'Different table names (compared '.$relation1['pivotTable']['name'].' with '.$relation2['pivotTable']['name'].').';
                        }

                        // check name
                        if (! empty(array_diff_key($relation1['pivotTable']['columns'], $relation2['pivotTable']['columns']))) {
                            $columns1 = implode(', ', array_keys($relation1['pivotTable']['columns']));
                            $columns2 = implode(', ', array_keys($relation2['pivotTable']['columns']));
                            $error = 'Different column names (compared '.$columns1.' with '.$columns2.').';
                        }

                        if ($error) {
                            throw new DomainException('Error syncing pivot tables for relations "'.$relation1['name'].'" in "'.$relation2['relatedEntity'].'" and "'.$relation2['name'].'" in "'.$relation1['relatedEntity'].'": '.$error);
                        }
                    }
                }
            }
        }
    }
}
