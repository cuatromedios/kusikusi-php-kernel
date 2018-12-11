<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class KusikusiModel extends Model
{

  protected $_lang;
  protected $hidden = ['relatedContents'];

  public function __construct(array $newAttributes = array())
  {
    if (!isset($newAttributes['id'])) {
      $generatedId =  Uuid::uuid4()->toString();
      $this->attributes['id'] = $generatedId;
    } else {
      $this->attributes['id'] = $newAttributes['id'];
    }
    parent::__construct($newAttributes);
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
   * Get the activity related to the EntityBase.
   */
  public function activity()
  {
    return $this->hasMany('Cuatromedios\Kusikusi\Models\Activity', 'entity_id');
  }

}
