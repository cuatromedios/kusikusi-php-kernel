<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\KusikusiModel;

class DataModel extends KusikusiModel
{

  public $modelId = 'no-model';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
  }

  /**
   * Get the Entity that is related to this instance.
   */
  public function entity()
  {
    return $this->belongsTo('App\Models\Entity', 'id');
  }

  public static function boot()
  {
    parent::boot();

    self::creating(function ($model)
    {
      $entity = new Entity([
          "id" => $model->id,
          "model" => self::classModelId(),
          "name" => ucfirst($model->id) //TODO: Get the title if it is comming in the model
      ]);
      $entity->save();
    });
  }


}
