<?php

namespace ProAI\Datamapper\Eloquent;

class GraphNode
{
    /**
     * Value of the field.
     *
     * @var string
     */
    protected $value;

    /**
     * Field description.
     *
     * @var string
     */
    protected $description;

    /**
     * Set value.
     *
     * @param string $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set description.
     *
     * @param string $value
     * @return void
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get value.
     *
     * @return void
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get description.
     *
     * @return void
     */
    public function getDescription()
    {
        return $this->description;
    }
}