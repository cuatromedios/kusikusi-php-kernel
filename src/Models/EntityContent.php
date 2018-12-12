<?php

namespace Cuatromedios\Kusikusi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class EntityContent extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'contents';

  /**
   * The primary key
   */
  protected $primaryKey = 'id';

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;
  protected $keyType = 'string';

  /**
   * Indicates  the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = true;

  /**
   * Get the EntityBase that owns the content.
   */
  public function entity()
  {
    return $this->belongsTo('App\Models\Entity');
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'id', 'entity_id', 'lang', 'field', 'value'
  ];

  /**
   * The attributes excluded from the model's JSON form.
   *
   * @var array
   */
  protected $hidden = ['id'];


  private static function compactContentsFields($entityOrCollection) {
    if (array_keys($entityOrCollection) !== range(0, count($entityOrCollection) - 1)) {
      $newContents = [];
      if (isset($entityOrCollection['contents'])) {
        foreach ($entityOrCollection['contents'] as $content) {
          $newContents[$content['field']] = $content['value'];
        }
      }
      if (isset($entityOrCollection['relations'])) {
        $grouped = EntityContent::compact($entityOrCollection['relations']);
        $entityOrCollection['relations'] = $grouped;
      }
      if (isset($entityOrCollection['entity'])) {
        $grouped = EntityContent::compact($entityOrCollection['entity']);
        $entityOrCollection['entity'] = $grouped;
      }
      $entityOrCollection["contents"] = $newContents;
      return $entityOrCollection;
    } else {
      return  array_map("Cuatromedios\Kusikusi\Models\EntityContent::compactContentsFields", $entityOrCollection);
    }
  }

  public static function compact($entityOrCollection) {
    if (!is_array($entityOrCollection)) {
      $entityOrCollection = $entityOrCollection->toArray();
    }
    return EntityContent::compactContentsFields($entityOrCollection);
  }

  public static function boot($preset = [])
  {

    parent::boot();

    self::saving(function ($model) {
      foreach ($model->attributes as $key=>$value) {
        if ( $key != "created_at" && $key != "updated_at" && ! in_array($key, $model->fillable)) {
          $model->attributes['field'] = $key;
          $model->attributes['value'] = $value;
          unset($model->attributes[$key]);
        }
      }
      $model->lang = $model->lang ?? Config::get('cms.langs')[0] ?? '';
      $model->id = $model->entity_id . "_" . $model->lang . "_" . $model->field;
    });
  }

}
