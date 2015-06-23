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
            $model->{$model->getKeyName()} = $model->generateUuid();
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
}