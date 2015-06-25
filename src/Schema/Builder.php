<?php

namespace ProAI\Datamapper\Schema;

use Illuminate\Database\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector;
use ProAI\Datamapper\Metadata\Definitions\Column as ColumnDefinition;
use ProAI\Datamapper\Metadata\Definitions\Table as TableDefinition;

class Builder
{
    /**
     * Laravel aliases for Doctrine DBAL types.
     *
     * @var array
     */
    protected $aliases = [
        // integers
        'bigInteger' => ['type' => 'bigint'],
        'smallInteger' => ['type' => 'smallint'],
        // chars
        'char' => ['type' => 'string', 'options' => ['fixed' => true]],
        // texts
        'text' => ['type' => 'text', 'options' => ['length' => 65535]],
        'mediumText' => ['type' => 'text', 'options' => ['length' => 16777215]],
        'longText' => ['type' => 'text', 'options' => ['length' => 4294967295]],
        // timestamps
        'dateTime' => ['type' => 'datetime', 'options' => ['default' => '0']],
        //'timestamp' => ['type' => 'datetime', 'options' => ['default' => '0', 'platformOptions' => ['version' => true]]],
    ];

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * Doctrine DBAL Schema Manager instance.
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $schemaManager;

    /**
     * Doctrine DBAL database platform instance.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $connection->getDoctrineSchemaManager();
        $this->platform = $this->schemaManager->getDatabasePlatform();
    }

    /**
     * Create all tables.
     *
     * @param array $metadata
     * @return array
     */
    public function create(array $metadata)
    {
        $schema = $this->getSchemaFromMetadata($metadata);

        $statements = $schema->toSql($this->platform);

        $this->build($statements);

        return $statements;
    }

    /**
     * Update all tables.
     *
     * @param array $metadata
     * @param boolean $saveMode
     * @return array
     */
    public function update(array $metadata, $saveMode=false)
    {
        $fromSchema = $this->schemaManager->createSchema();
        $toSchema = $this->getSchemaFromMetadata($metadata);

        $comparator = new Comparator;
        $schemaDiff = $comparator->compare($fromSchema, $toSchema);

        if ($saveMode) {
            $statements = $schemaDiff->toSaveSql($this->platform);
        } else {
            $statements = $schemaDiff->toSql($this->platform);
        }

        $this->build($statements);

        return $statements;
    }

    /**
     * Drop all tables.
     *
     * @param array $metadata
     * @return array
     */
    public function drop(array $metadata)
    {
        $visitor = new DropSchemaSqlCollector($this->platform);

        $schema = $this->getSchemaFromMetadata($metadata);

        $fullSchema = $this->schemaManager->createSchema();

        foreach ($fullSchema->getTables() as $table) {
            if ($schema->hasTable($table->getName())) {
                $visitor->acceptTable($table);
            }
        }

        $statements = $visitor->getQueries();

        $this->build($statements);

        return $statements;
    }

    /**
     * Execute the statements against the database.
     *
     * @param  array $statements
     * @return void
     */
    protected function build($statements)
    {
        foreach ($statements as $statement) {
            $this->connection->statement($statement);
        }
    }

    /**
     * Create schema instance from metadata
     *
     * @param array $metadata
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function getSchemaFromMetadata(array $metadata)
    {
        $entityMetadataSchemaConfig = $this->schemaManager->createSchemaConfig();
        $schema = new Schema([], [], $entityMetadataSchemaConfig);
        $pivotTables = [];

        foreach ($metadata as $entityMetadata) {
            $this->generateTableFromMetadata($schema, $entityMetadata['table']);
            // create version table
            if (! empty($entityMetadata['versionTable'])) {
                $this->generateTableFromMetadata($schema, $entityMetadata['versionTable']);
            }

            foreach ($entityMetadata['relations'] as $relationMetadata) {
                if (! empty($relationMetadata['pivotTable'])) {
                    // create pivot table for many to many relations
                    if (! in_array($relationMetadata['pivotTable']['name'], $pivotTables)) {
                        $this->generateTableFromMetadata($schema, $relationMetadata['pivotTable']);
                    }

                    $pivotTables[] = $relationMetadata['pivotTable']['name'];
                }
            }
        }

        return $schema;
    }

    /**
     * Generate a table from metadata.
     *
     * @param table \Doctrine\DBAL\Schema\Schema
     * @param \ProAI\Datamapper\Metadata\Definitions\Table $tableMetadata
     * @return void
     */
    protected function generateTableFromMetadata($schema, TableDefinition $tableMetadata)
    {
        $primaryKeys = [];
        $uniqueIndexes = [];
        $indexes = [];

        $table = $schema->createTable($this->connection->getTablePrefix().$tableMetadata['name']);

        foreach ($tableMetadata['columns'] as $columnMetadata) {
            $columnMetadata = $this->getDoctrineColumnAliases($columnMetadata);

            // add column
            $options = $this->getDoctrineColumnOptions($columnMetadata);
            $table->addColumn($columnMetadata['name'], $columnMetadata['type'], $options);

            // add primary keys, unique indexes and indexes
            if (! empty($columnMetadata['primary'])) {
                $primaryKeys[] = $columnMetadata['name'];
            }
            if (! empty($columnMetadata['unique'])) {
                $uniqueIndexes[] = $columnMetadata['name'];
            }
            if (! empty($columnMetadata['index'])) {
                $indexes[] = $columnMetadata['name'];
            }
        }

        // add primary keys, unique indexes and indexes
        if (! empty($primaryKeys)) {
            $table->setPrimaryKey($primaryKeys);
        }
        if (! empty($uniqueIndexes)) {
            $table->addUniqueIndex($uniqueIndexes);
        }
        if (! empty($indexes)) {
            $table->addIndex($indexes);
        }
    }

    /**
     * Get the doctrine column type.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Column $columnMetadata
     * @return array
     */
    protected function getDoctrineColumnAliases(ColumnDefinition $columnMetadata)
    {
        if (in_array($columnMetadata['type'], array_keys($this->aliases))) {
            $index = $columnMetadata['type'];

            // fix for nullable datetime
            if ($columnMetadata['type'] == 'dateTime' && ! empty($columnMetadata['nullable'])) {
                $this->aliases['dateTime']['options'] = array_except($this->aliases['dateTime']['options'], 'default');
            }

            // update primary key
            if (! empty($this->aliases[$index]['primary'])) {
                $columnMetadata['primary'] = $this->aliases[$index]['primary'];
            }

            // update unsigned
            if (! empty($this->aliases[$index]['options']['unsigned'])) {
                $columnMetadata['unsigned'] = $this->aliases[$index]['options']['unsigned'];
            }

            // update options
            $columnMetadata['options'] = array_merge($columnMetadata['options'], $this->aliases[$index]['options']);

            // update type
            $columnMetadata['type'] = $this->aliases[$index]['type'];
        }

        return $columnMetadata;
    }

    /**
     * Get the doctrine column options.
     *
     * @param \ProAI\Datamapper\Metadata\Definitions\Column $columnMetadata
     * @return array
     */
    protected function getDoctrineColumnOptions(ColumnDefinition $columnMetadata)
    {
        $options = $columnMetadata['options'];

        // alias for nullable option
        if (! empty($columnMetadata['nullable'])) {
            $options['notnull'] = ! $columnMetadata['nullable'];
        }

        // alias for default option
        if (! empty($columnMetadata['default'])) {
            $options['default'] = $columnMetadata['default'];
        }

        // alias for unsigned option
        if (! empty($columnMetadata['options']['unsigned'])) {
            $options['unsigned'] = $columnMetadata['options']['unsigned'];
        }

        // alias for autoincrement option
        if (! empty($columnMetadata['options']['autoIncrement'])) {
            $options['autoincrement'] = $columnMetadata['options']['autoIncrement'];
        }

        // alias for fixed option
        if (! empty($columnMetadata['options']['fixed'])) {
            $options['fixed'] = $columnMetadata['options']['fixed'];
        }

        return $options;
    }
}
