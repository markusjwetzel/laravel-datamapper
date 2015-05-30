<?php namespace Wetzel\Datamapper\Support\Facades;

/**
 * @see \Wetzel\Datamapper\EntityManager
 */
class EntityManager extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'entity';
	}

}