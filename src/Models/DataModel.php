<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Models\KusikusiModel;

class DataModel extends KusikusiModel
{

  public $modelId = 'nomodel';
  protected $table = 'nodata';
  protected $_entity;
  protected $appends = array('entity');
  protected $hidden = array('relatedEntity');

  /**
   * Set the relation to an Entity.
   */
  public function relatedEntity() {
    return $this->belongsTo('App\Models\Entity', 'id');
  }

  /**
   * Mutator to create or update the entity alongside the related model
   * @param array $value
   */
  public function setEntityAttribute($values) {
    $merged_values = array_merge(["id" => $this->attributes['id']], ["model" => self::modelId()], $values);
    $this->_entity = Entity::firstOrNew(["id" => $this->attributes['id']], $merged_values);
    $this->_entity->attributes = array_merge($this->_entity->attributes, $values);
  }

  /**
   * Mutator to retrieve alongside the related model
   * @return Entity Returns the related entity
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
