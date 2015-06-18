<?php

namespace Wetzel\Datamapper\Presenter;

use Exception;

class Decorator
{
    /**
     * Decorate an item.
     *
     * @param  mixed  $item
     * @param  boolean  $toArray
     * @return mixed
     */
    public static function decorate($item, $toArray=false)
    {
        // make item presentable
        if ($item instanceof \Wetzel\Datamapper\Contracts\Presentable) {
            if ($toArray) {
                if ($item instanceof \Wetzel\Datamapper\Contracts\Stringable) {
                    return $item->toString();
                }
                if ($item instanceof \Illuminate\Contracts\Support\Arrayable) {
                    return $item->toArray();
                }
            } else {
                return $item->getPresenter();
            }
        }

        // item is collection/paginator
        if (self::isCollection($item)) {
            // decorate collection items
            foreach ($item as $key => $collectionItem) {
                $item[$key] = self::decorate($collectionItem, $toArray);
            }

            if ($toArray) {
                return $item->toArray();
            } else {
                return $item;
            }
        }

        // throw exception if unknown item was not converted in case of array conversion
        if ($toArray && is_object($item) && ! self::isCollection($item)) {
            // item is proxy
            if ($item instanceof \Wetzel\Datamapper\Contracts\Proxy) {
                return $item->toArray();
            }

            throw new Exception('Array conversion failed, because object "'.get_class($item).'" was not converted.');
        }

        return $item;
    }

    /**
     * Check if item is collection.
     *
     * @param  mixed  $item
     * @return boolean
     */
    protected static function isCollection($item)
    {
        if ($item instanceof \Illuminate\Support\Collection) {
            return true;
        }

        if ($item instanceof \Illuminate\Contracts\Pagination\Paginator) {
            return true;
        }

        return false;
    }
}
