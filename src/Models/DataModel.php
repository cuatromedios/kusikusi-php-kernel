<?php

namespace Cuatromedios\Kusikusi\Models;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Cuatromedios\Kusikusi\Models\KusikusiModel;

class DataModel extends KusikusiModel
{

  const NO_DATA_TABLE = "nodata";
  public $modelId = 'nomodel';
  protected $table = DataModel::NO_DATA_TABLE;
  protected $_entity;
  // protected $appends = ['entity', 'contents'];
  // protected $with = ['relatedContents', 'relatedEntity'];
  protected $hidden = ['relatedEntity', 'relatedContents'];

  public function __construct(array $newAttributes = array(), $lang = NULL)
  {
    parent::__construct($newAttributes, $lang);
    $this->_entity = Entity::firstOrNew(["id" => $this->attributes['id']], ["model" => self::modelId()]);
  }

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

    self::creating(function ($model) {
      if ($model->table == DataModel::NO_DATA_TABLE) {
        $model->model = self::modelId();
      }
    });
    self::saved(function ($model) {
        $model->_entity->clearContents();
        $model->_entity->save();
    });
    self::retrieved(function ($model) {
      $model->_entity = $model->relatedEntity;
    });
    static::addGlobalScope(new ModelScope);
  }

}

class ModelScope implements Scope
{
  /**
   * Apply the scope to a given Eloquent query builder.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $builder
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @return void
   */
  public function apply(Builder $builder, Model $model)
  {
    if ($model->table == DataModel::NO_DATA_TABLE) {
      $builder->where('model', self::modelId());
    }
  }
}
