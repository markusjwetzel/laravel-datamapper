<?php namespace Wetzel\Datamapper\Eloquent;

class Generator {

    /**
     * Model stub.
     * @var string
     */
    protected $stub;

    /**
     * Model relation stub.
     * @var string
     */
    protected $relationStub;

    /**
     * Constructor.
     *
     * @param string $stub
     * @param string $relationStub
     * @return void
     */
    public function __construct($stub, $relationStub) {
        $this->stub = $stub;
        $this->stub = $relationStub;
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadata
     * @return string
     */
    public function generateModel($metadata)
    {
        $stub = $this->stub;

        $this->replaceSoftDeletes($stub, $metadata['softDeletes']);
        $this->replaceTable($stub, $metadata['table']['name']);
        $this->replacePrimaryKey($stub, $metadata['primarykey']);
        $this->replaceIncrementing($stub, $metadata['incrementing']);
        $this->replaceTimestamps($stub, $metadata['timestamps']);
        $this->replaceAttributes($stub, $metadata['attributes']);
        $this->replaceEmbeddeds($stub, $metadata['embeddeds']);
        $this->replaceHidden($stub, $metadata['hidden']);
        $this->replaceVisible($stub, $metadata['visible']);
        $this->replaceAppends($stub, $metadata['appends']);
        $this->replaceFillable($stub, $metadata['fillable']);
        $this->replaceDates($stub, $metadata['dates']);
        $this->replaceTouches($stub, $metadata['touches']);
        $this->replaceRelations($stub, $metadata['relations']);

        return $stub;
    }
    
    /**
     * Replaces soft deletes.
     *
     * @param string $stub
     * @param boolean $option
     * @return void
     */
    protected function replaceSoftDeletes(&$stub, $option)
    {
        str_replace('{{softDeletes}}', $option ? 'use SoftDeletes;' : '' , $stub);
    }
    
    /**
     * Replaces table name.
     * 
     * @param string $stub
     * @param boolean $name
     * @return void
     */
    protected function replaceTable(&$stub, $name)
    {
        str_replace('{{table}}', "'".$name."'", $stub);
    }
    
    /**
     * Replaces primary key.
     * 
     * @param string $stub
     * @param string $name
     * @return void
     */
    protected function replacePrimaryKey(&$stub, $name)
    {
        str_replace('{{primarykey}}', "'".$name."'", $stub);
    }
    
    /**
     * Replaces incrementing.
     * 
     * @param string $stub
     * @param boolean $option
     * @return void
     */
    protected function replaceIncrementing(&$stub, $option)
    {
        str_replace('{{incrementing}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces timestamps.
     * 
     * @param string $stub
     * @param boolean $option
     * @return void
     */
    protected function replaceTimestamps(&$stub, $option)
    {
        str_replace('{{timestamps}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces attributes.
     * 
     * @param string $stub
     * @param array $attributes
     * @return void
     */
    protected function replaceAttributes(&$stub, $attributes)
    {
        //str_replace('{{attributes}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces embeddeds.
     * 
     * @param string $stub
     * @param array $embeddeds
     * @return void
     */
    protected function replaceEmbeddeds(&$stub, $embeddeds)
    {
        //str_replace('{{embeddeds}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces hidden.
     * 
     * @param string $stub
     * @param array $hidden
     * @return void
     */
    protected function replaceHidden(&$stub, $hidden)
    {
        //str_replace('{{hidden}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces visible.
     * 
     * @param string $stub
     * @param array $visible
     * @return void
     */
    protected function replaceVisible(&$stub, $visible)
    {
        //str_replace('{{visible}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces appends.
     * 
     * @param string $stub
     * @param array $appends
     * @return void
     */
    protected function replaceAppends(&$stub, $appends)
    {
        //str_replace('{{appends}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces fillable.
     * 
     * @param string $stub
     * @param array $fillable
     * @return void
     */
    protected function replaceFillable(&$stub, $fillable)
    {
        //str_replace('{{fillable}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces dates.
     * 
     * @param string $stub
     * @param array $dates
     * @return void
     */
    protected function replaceDates(&$stub, $dates)
    {
        //str_replace('{{dates}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces touches.
     * 
     * @param string $stub
     * @param array $touches
     * @return void
     */
    protected function replaceTouches(&$stub, $touches)
    {
        //str_replace('{{touches}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces relations.
     * 
     * @param string $stub
     * @param array $relations
     * @return void
     */
    protected function replaceRelations(&$stub, $relations)
    {
        $relations = [];

        foreach($relations as $relation) {
            $relationStub = $this->relationStub;

            $options = implode(",",array_merge($relation['related'], $relation['options']));
            // todo: merge by relation

            str_replace('{{name}}', $relation['name'], $relationStub);
            str_replace('{{options}}', $options, $relationStub);
            str_replace('{{ucfirst_type}}', ucfirst($relation['type']), $relationStub);
            str_replace('{{type}}', $relation['type'], $relationStub);

            $relations[] = $relationStub;
        }

        str_replace('{{relations}}', implode(PHP_EOL . PHP_EOL, $relations), $stub);
    }

}