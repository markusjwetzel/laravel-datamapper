<?php namespace Wetzel\Datamapper\Presenter;

use Wetzel\Datamapper\Support\Presenter;

class Storage {

    /**
     * The presentable classes.
     *
     * @var array
     */
    public static $instances = [];

    /**
     * Set the instance.
     *
     * @param string $key
     * @param \Wetzel\Datamapper\Support\Presenter $instance
     * @return array
     */
    public static function add($key, Presenter $instance)
    {
        static::$instances[$key] = $instance;
    }

    /**
     * Has the instance.
     *
     * @param string $key
     * @return array
     */
    public static function has($key)
    {
        return (isset(static::$instances[$key]));
    }

    /**
     * Get the instance.
     *
     * @param string $key
     * @return array
     */
    public static function get($key)
    {
        return static::$instances[$key];
    }

}
