<?php

include 'EloquentModel.php';
include '../src/Model.php';
include '../src/Mapping.php';

include 'Model/User.php';

use ProAI\Datamapper\Mapping;
use ProAI\Datamapper\Model;

$mapping = new Mapping('Cribbb\Domain\Model\Identity\User');

$model = new Model();
$model->setAttribute();
var_dump($model->attributes);

print "<pre><code>";
var_dump($mapping->getPropertyAnnotations());
var_dump($mapping->getClassAnnotations());
print "</pre></code>";