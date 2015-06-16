<?php

namespace Wetzel\Datamapper\Contracts;

interface Model
{
    /**
     * Get the presenter instance of this model.
     *
     * @return \Wetzel\Datamapper\Support\Presenter
     */
    public function getPresenter();
    
    /**
     * Convert the entity instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
    
    /**
     * Convert the entity instance to an array.
     *
     * @return array
     */
    public function toArray();
}
