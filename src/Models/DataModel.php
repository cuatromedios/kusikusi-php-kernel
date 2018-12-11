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

  public function __construct(array $newAttributes = array(), $lang = NULL)
  {
    parent::__construct($newAttributes, $lang);
  }

  /**
   * Set the relation to an Entity.
   */
  public function entity() {
    return $this->belongsTo('App\Models\Entity', 'id');
  }

  public static function boot() {
    parent::boot();
    self::creating(function ($model) {
      if ($model->table == DataModel::NO_DATA_TABLE) {
        $model->model = self::modelId();
      }
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
