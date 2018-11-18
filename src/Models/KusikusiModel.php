<?php

namespace Cuatromedios\Kusikusi\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class KusikusiModel extends Model
{

  protected $_id;

  public function __construct(array $attributes = array())
  {
    if (!isset($attributes['id'])) {
      $generatedId =  Uuid::uuid4()->toString();
      $attributes['id'] = $generatedId;
      $this->_id = $generatedId;
    } else {
      $this->_id = $attributes['id'];
    }

    parent::__construct($attributes);
  }

  /**
   * The primary key is the same id than the related EntityBase
   */
  protected $primaryKey = 'id';
  public $incrementing = false;


  /**
   * Get the class name as a kebab string
   * @return string
   */
  public function instanceModelId()
  {
    return self::modelId();
  }
  public static function modelId()
  {
    return $model['model'] = kebab_case(class_basename(get_called_class()));
  }


  /**
   * Indicates  the model should be timestamped.
   * @var bool
   */
  public $timestamps = false;


  /**
   * Set the contents relation of the EntityBase.
   */
  public function relatedContents()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\EntityContent', 'entity_id');
  }
  /**
   * Mutator to create or update the entity alongside the related model
   * @param $value
   */
  public function setContentsAttribute(array $value)
  {

  }

  /**
   * Mutator to create or update the entity alongside the related model
   * @return Returns the original relation
   */
  public function getContentsAttribute()
  {
    return $this->relatedContents;
  }



  /**
   * Get the activity related to the EntityBase.
   */
  public function activity()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\Activity', 'entity_id');
  }

  /**
   * Get the related entities
   */

  public function relations()
  {
    return $this->belongsToMany('App\Models\Entity', 'relations', 'caller_entity_id', 'called_entity_id')
        ->using('Cuatromedios\Kusikusi\Models\Relation')
        ->as('relations')
        ->withPivot('kind', 'position', 'depth', 'tags')
        ->withTimestamps();
  }

  /**
   * The attributes excluded from the model's JSON form.
   * @var array
   */
  protected $hidden = [];

  public static function boot() {

    parent::boot();

    self::retrieved(function ($model) {
      $model->_id = $model->id;
    });
  }
}
