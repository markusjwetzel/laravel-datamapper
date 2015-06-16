<?php

if (! function_exists('get_real_entity')) {
    /**
     * Get the real entity namespace.
     *
     * @param string $class
     * @return string
     */
    function get_real_entity($class)
    {
        $base_namespace = app('config')['datamapper']['base_namespace'];

        if (class_exists($base_namespace . '\\' . $class)) {
            return $base_namespace . '\\' . $class;
        } elseif (class_exists($class)) {
            return $class;
        } else {
            throw new Exception('Entity class "'.$class.'" does not exist.');
        }
    }
}

if (! function_exists('get_mapped_model_hash')) {
    /**
     * Get the hash of an entity that is used for the mapped eloquent model.
     *
     * @param string $class
     * @return string
     */
    function get_mapped_model_hash($class)
    {
        return md5($class);
    }
}

if (! function_exists('get_mapped_model_namespace')) {
    /**
     * Get the namespace of an entity that is used for the mapped eloquent model.
     *
     * @return string
     */
    function get_mapped_model_namespace()
    {
        return 'Wetzel\Datamapper\Cache';
    }
}

if (! function_exists('get_mapped_model')) {
    /**
     * Get the classname of an entity that is used for the mapped eloquent model.
     *
     * @param string $class
     * @return string
     */
    function get_mapped_model($class)
    {
        $model = get_mapped_model_namespace() . '\Entity' . get_mapped_model_hash($class);
        if (class_exists($model)) {
            return $model;
        } else {
            throw new Exception('There is no mapped Eloquent class for class "'.$class.'".');
        }
    }
}
