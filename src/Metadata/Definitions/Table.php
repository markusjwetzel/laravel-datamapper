<?php

namespace ProAI\Datamapper\Metadata\Definitions;

class Table extends Definition
{
    /**
     * Valid keys.
     *
     * @var array
     */
    protected $keys = [
        'name' => null,
        'columns' => [],
    ];
}
