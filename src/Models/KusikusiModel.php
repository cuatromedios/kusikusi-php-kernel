<?php

namespace Cuatromedios\Kusikusi\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class KusikusiModel extends Model
{

  /**
   * The primary key is the same id than the related EntityBase
   */
  protected $primaryKey = 'id';
  public $incrementing = false;

  protected $table = 'nodata';


  /**
   * Get the class name as a kebab string
   * @return string
   */
  public function instanceModelId()
  {
    return self::classModelId();
  }
  public static function classModelId()
  {
    return $model['model'] = kebab_case(class_basename(get_called_class()));
  }


  /**
   * Indicates  the model should be timestamped.
   * @var bool
   */
  public $timestamps = false;


  /**
   * Get the contents of the EntityBase.
   */
  public function contents()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\EntityContent', 'entity_id');
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

  /**
   * Actions to be done on boot
   */
  public static function boot()
  {
    parent::boot();

    self::creating(function ($model)
    {
      // Auto populate the id field
      if (!isset($model['id'])) {
        $model['id'] = Uuid::uuid4()->toString();
      }
    });
  }

}
