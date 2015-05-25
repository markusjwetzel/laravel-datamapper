<?php namespace Wetzel\DataMapper\Database;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Visitor\DropSchemaSqlCollector;
use Wetzel\DataMapper\Metadata\Definitions\Class as ClassDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Column as ColumnDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Relation as RelationDefinition;

class Builder {

    /**
     * Laravel aliases for Doctrine DBAL types.
     *
     * @var array
     */
    protected $aliases = [
        // integers
        'bigInteger' => ['type' => 'bigint'],
        'smallInteger' => ['type' => 'smallint'],
        'increments' => ['type' => 'integer', 'primary' => true, 'unsigned' => true, 'options' => ['autoincrement' => true]],
        'bigIncrements' => ['type' => 'bigint', 'primary' => true, 'unsigned' => true, 'options' => ['autoincrement' => true]],
        // chars
        'char' => ['type' => 'string', 'options' => ['fixed' => true]],
        // texts
        'text' => ['type' => 'text', 'options' => ['length' => 65535]],
        'mediumText' => ['type' => 'text', 'options' => ['length' => 16777215]],
        'longText' => ['type' => 'text', 'options' => ['length' => 4294967295]],
        // timestamps
        'timestamp' => ['type' => 'datetime', 'options' => ['platformOptions' => ['version' => true]]],
    ];

    /**
     * Doctrine DBAL supported Laravel Schema Builder types.
     *
     * @var array
     */
    protected $supported = [
        'bigIncrements' => 1,
        'bigInteger' => 1,
        'binary' => 1,
        'boolean' => 1,
        'char' => 1,
        'date' => 1,
        'dateTime' => 1,
        'decimal' => 1,
        'double' => 0,
        'enum' => 0,
        'float' => 1,
        'increments' => 1,
        'integer' => 1,
        'json' => 0,
        'jsonb' => 0,
        'longText' => 1,
        'mediumInteger' => 0,
        'mediumText' => 1,
        'smallInteger' => 1,
        'tinyInteger' => 0,
        'string' => 1,
        'text' => 1,
        'time' => 1,
        'timestamp' => 1,
    ];

    /**
     * Doctrine DBAL unsupported types.
     *
     * @var array
     */
    protected $unsupportedByDoctrine = [
        'tinyInteger',
        'mediumInteger',
        'enum',
        'double',
        'json',
        'jsonb',
    ];

    /**
     * Laravel Schema Builder unsupported types.
     *
     * @var array
     */
    protected $unsupportedByLaravel = [
        'blob',
        'datetimetz',
        'array',
        'json_array',
        'object',
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
        $this->platform = $connection->getDatabasePlatform();
    }

    /**
     * Creates all tables.
     *
     * @param array $metadataArray
     * @return array
     */
    public function createSchema(array $metadataArray)
    {
        $schema = $this->getSchemaFromMetadata($metadataArray);

        $statements = $schema->toSql($this->platform);

        $this->build($statements);

        return $statements;
    }

    /**
     * Updates all tables.
     *
     * @param array $metadataArray
     * @param boolean $saveMode
     * @return array
     */
    public function updateSchema(array $metadataArray, $saveMode=false)
    {
        $fromSchema = $this->schemaManager->createSchema();
        $toSchema = $this->getSchemaFromMetadata($metadataArray);

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
     * Drops all tables.
     *
     * @param array $metadataArray
     * @return array
     */
    public function dropSchema(array $metadataArray)
    {
        $visitor = new DropSchemaSqlCollector($this->platform);

        $schema = $this->getSchemaFromMetadata($metadataArray);

        $fullSchema = $this->schemaManager->createSchema();

        foreach ($fullSchema->getTables() AS $table) {
            if ($schema->hasTable($table->getName())) {
                $visitor->acceptTable($table);
            }
        }

        $statements = $visitor->getQueries();

        $this->build($statements);

        return $statements;
    }

    /**
     * Creates a table from metadata.
     *
     * @param array $metadata
     * @return boolean
     */
    /*public function createTable($metadata)
    {
        $table = $this->generateTableSchema($metadata);

        $statements = (array) $this->schemaManager->getDatabasePlatform()->getCreateTableSQL($table);

        $this->build($statements);
    }*/

    /**
     * Compares table columns with metadata and updates a table.
     *
     * @param array $metadata
     * @return string
     */
    /*public function updateTable($metadata)
    {
        $newTable = $this->generateTableSchema($metadata);

        $oldTable = $schema->listTableDetails($this->connection->getTablePrefix().$metadata['table']);

        $tableDiff = (new Comparator)->diffTable($newTable, $oldTable);

        if ($tableDiff !== false)
        {
            $statements = (array) $this->schemaManager->getDatabasePlatform()->getAlterTableSQL($tableDiff);

            $this->build($statements);
        }
    }*/

    /**
     * Drops a table.
     *
     * @return string
     */
    /*public function dropTable($metadata)
    {
        $statement = $this->schemaManager->getDatabasePlatform()->getTruncateTableSQL($this->connection->getTablePrefix().$metadata['table']);

        $this->build([$statement]);
    }*/

    /**
     * Execute the statements against the database.
     *
     * @param  array $statements
     * @return void
     */
    protected function build($statements)
    {
        foreach ($statements as $statement)
        {
            $this->connection->statement($statement);
        }
    }

    /**
     * Creates schema instance from metadata
     *
     * @param array $metadataArray
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function getSchemaFromMetadata(array $metadataArray)
    {
        $metadataSchemaConfig = $this->schemaManager->createSchemaConfig();
        $schema = new Schema([], [], $metadataSchemaConfig);

        foreach ($metadataArray as $metadata) {
            $this->generateTableFromMetadata($schema, $metadata);
        }

        return $schema;
    }

    /**
     * Generates a table from metadata.
     *
     * @param table \Doctrine\DBAL\Schema\Schema
     * @param \Wetzel\DataMapper\Metadata\Definitions\Class $metadata
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected function generateTableFromMetadata($schema, ClassDefinition $metadata)
    {
        $primaryKeys = [];
        $uniqueIndexes = [];
        $indexes = [];

        $table = $schema->createTable($this->connection->getTablePrefix().$metadata['table']);

        foreach($metadata['columns'] as $name => $columnMetadata) {
            $columnMetadata = $this->getDoctrineColumnAliases($columnMetadata);

            // add column
            $options = $this->getDoctrineColumnOptions($columnMetadata);
            $table->addColumn($name, $columnMetadata['type'], $options);

            // add primary keys, unique indexes and indexes
            if ( ! empty($columnMetadata['primary']))
                $primaryKeys[] = $name;
            if ( ! empty($columnMetadata['unique']))
                $uniqueIndexes[] = $name;
            if ( ! empty($columnMetadata['index']))
                $indexes[] = $name;
        }

        foreach($metadata['relations'] as $name => $relationMetadata) {
            // create pivot table for many to many relations
            if ($metadata['relations']['type'] == 'hasMany') {
                $this->generatePivotTableFromMetadata($schema, $name, $relationMetadata);
            }
        }

        // add primary keys, unique indexes and indexes
        if ( ! empty($primaryKeys))
            $table->setPrimaryKey($primaryKeys);
        if ( ! empty($uniqueIndexes))
            $table->addUniqueIndex($uniqueIndexes);
        if ( ! empty($indexes))
            $table->addIndex($indexes);

        return $table;
    }

    /**
     * Generates a table from metadata.
     *
     * @param table \Doctrine\DBAL\Schema\Schema
     * @param name $name
     * @param \Wetzel\DataMapper\Metadata\Definitions\Relation $relationMetadata
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected function generatePivotTableFromMetadata($schema, $name, RelationDefinition $relationMetadata)
    {
        // todo
    }

    /**
     * Get the doctrine column type.
     *
     * @param \Wetzel\DataMapper\Metadata\Definitions\Column $columnMetadata
     * @return array
     */
    protected function getDoctrineColumnAliases(ColumnDefinition $columnMetadata)
    {
        if (in_array($columnMetadata['type'], $this->aliases)) {
            $index = $columnMetadata['type'];

            // update primary key
            if ( ! empty($this->aliases[$index]['primary'])) {
                $columnMetadata['primary'] = $this->aliases[$index]['primary'];
            }

            // update unsigned
            if ( ! empty($this->aliases[$index]['unsigned'])) {
                $columnMetadata['unsigned'] = $this->aliases[$index]['unsigned'];
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
     * @param \Wetzel\DataMapper\Metadata\Definitions\Column $columnMetadata
     * @return array
     */
    protected function getDoctrineColumnOptions(ColumnDefinition $columnMetadata)
    {
        $options = $columnMetadata['options'];

        // alias for nullable option
        if ( ! empty($columnMetadata['nullable'])) {
            $options['notnull'] = ! $columnMetadata['nullable'];
        }

        // alias for default option
        if ( ! empty($columnMetadata['default'])) {
            $options['default'] = $columnMetadata['default'];
        }

        // alias for unsigned option
        if ( ! empty($columnMetadata['unsigned'])) {
            $options['unsigned'] = $columnMetadata['unsigned'];
        }

        return $options;
    }

    /**
     * Get all tablenames of a database.
     *
     * @param string $table
     * @return void
     */
    /*protected function getTableNames() {
        return $this->connection->select($this->grammar->compileTableExists());
    }*/

    /**
     * Get all columnnames of a table.
     *
     * @param string $table
     * @return void
     */
    /*protected function getColumnNames($table) {
        return $this->schemaManager->getColumnListing($table);
    }*/

}