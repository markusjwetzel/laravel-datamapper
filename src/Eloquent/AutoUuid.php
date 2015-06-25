<?php

namespace ProAI\Datamapper\Eloquent;

use Ramsey\Uuid\Uuid;

trait AutoUuid
{
    /**
     * Boot the uuid trait for a model.
     *
     * @return void
     */
    protected static function bootAutoUuid()
    {
        static::creating(function ($model) {
            foreach($model->getAutoUuids() as $autoUuid) {
                $model->setAttribute($autoUuid, $model->generateUuid()->getBytes());
            }
        });
    }

    /**
     * Get a new UUID.
     *
     * @return \Ramsey\Uuid\Uuid
     */
    public function generateUuid()
    {
        return Uuid::uuid4();
    }

    /**
     * Get the auto generated uuid columns array.
     *
     * @return array
     */
    public function getAutoUuids()
    {
        return $this->autoUuids;
    }
}
