<?php namespace Wetzel\DataMapper\Support\Facades;

/**
 * @see \Wetzel\DataMapper\EntityManager
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