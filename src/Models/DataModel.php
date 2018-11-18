<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\KusikusiModel;

class DataModel extends KusikusiModel
{

  public $modelId = 'no-model';
  protected $table = 'nodata';
  private $_entity;

  /**
   * Get the Entity that is related to this instance.
   */
  public function relatedEntity() {
    return $this->belongsTo('App\Models\Entity', 'id');
  }

  /**
   * Mutator to create or update the entity alongside the related model
   * @param $value
   */
  public function setEntityAttribute($values) {
    $merged_values = array_merge(["id" => $this->_id], ["model" => self::modelId()], $values);
    $this->_entity = Entity::firstOrNew(["id" => $this->_id], $merged_values);
    $this->_entity->attributes = array_merge($this->_entity->attributes, $values);
  }

  /**
   * Mutator to create or update the entity alongside the related model
   * @return Returns the original relation
   */
  public function getEntityAttribute() {
    return $this->_entity;
  }

  public static function boot() {

    parent::boot();

    self::saving(function ($model) {
      $model->model = self::modelId();
    });
    self::saved(function ($model) {
      $model->_entity->save();
    });
    self::retrieved(function ($model) {
      $model->_entity = $model->relatedEntity;
    });
    static::addGlobalScope('model', function ($builder) {
      $builder->where('model', self::modelId());
    });
  }

}
